"""
Northline 3D: a two-stage spur gearbox that assembles over the animation.
Frame 0 = exploded, last frame = assembled. Rendered with Cycles to a transparent
frame sequence (shadow catcher keeps the contact shadow) for the scroll-scrubbed section.

Run:  blender -b --factory-startup --python tools/blender/gearbox.py -- --out <dir> [--frames 60] [--res 960]
      [--preview <frame>]  renders one frame only.
      [--save <file.blend>] saves the scene.
"""
import bpy, bmesh, math, sys, os
from mathutils import Vector

argv = sys.argv[sys.argv.index("--") + 1:] if "--" in sys.argv else []
def arg(name, default=None):
    return argv[argv.index(name) + 1] if name in argv else default

OUT = arg("--out", "//frames")
FRAMES = int(arg("--frames", 60))
RES = int(arg("--res", 960))
PREVIEW = arg("--preview")
SAVE = arg("--save")
SAMPLES = int(arg("--samples", 160))

# ---------------------------------------------------------------- scene reset
bpy.ops.wm.read_factory_settings(use_empty=True)
scene = bpy.context.scene
scene.frame_start, scene.frame_end = 0, FRAMES - 1

# ---------------------------------------------------------------- materials
def mat(name, color, metallic, rough, aniso=0.0, coat=0.0, spec_tint=None):
    m = bpy.data.materials.new(name)
    m.use_nodes = True
    b = m.node_tree.nodes["Principled BSDF"]
    b.inputs["Base Color"].default_value = (*color, 1)
    b.inputs["Metallic"].default_value = metallic
    b.inputs["Roughness"].default_value = rough
    if "Anisotropic" in b.inputs:
        b.inputs["Anisotropic"].default_value = aniso
    if "Coat Weight" in b.inputs:
        b.inputs["Coat Weight"].default_value = coat
    # Fine machining noise on roughness for realism.
    nt = m.node_tree
    noise = nt.nodes.new("ShaderNodeTexNoise")
    noise.inputs["Scale"].default_value = 180.0
    noise.inputs["Detail"].default_value = 8.0
    ramp = nt.nodes.new("ShaderNodeMapRange")
    ramp.inputs["To Min"].default_value = max(0.0, rough - 0.08)
    ramp.inputs["To Max"].default_value = min(1.0, rough + 0.08)
    nt.links.new(noise.outputs["Fac"], ramp.inputs["Value"])
    nt.links.new(ramp.outputs["Result"], b.inputs["Roughness"])
    return m

def srgb(h):
    h = h.lstrip("#")
    c = [int(h[i:i + 2], 16) / 255 for i in (0, 2, 4)]
    return tuple(((x + 0.055) / 1.055) ** 2.4 if x > 0.04045 else x / 12.92 for x in c)

M_STEEL   = mat("Brushed steel", (0.46, 0.47, 0.49), 1.0, 0.30, aniso=0.7)
M_DARK    = mat("Oiled steel", (0.24, 0.25, 0.27), 1.0, 0.30)
M_POLISH  = mat("Ground shaft", (0.55, 0.56, 0.57), 1.0, 0.2, aniso=0.5)
M_HOUSING = mat("Anodized housing", srgb("#2d3e4e"), 0.85, 0.46, coat=0.1)
M_OXIDE   = mat("Oxide anodized", tuple(c * 0.8 for c in srgb("#b4471b")), 1.0, 0.36, coat=0.1)
M_BLACK   = mat("Black oxide", (0.03, 0.03, 0.035), 0.9, 0.42)
M_BRASS   = mat("Bronze bushing", (0.72, 0.52, 0.30), 1.0, 0.28)

# ---------------------------------------------------------------- geometry helpers
def finish(obj, material, bevel=0.0025, segments=3):
    obj.data.materials.append(material)
    for p in obj.data.polygons:
        p.use_smooth = True
    if bevel:
        bv = obj.modifiers.new("Bevel", "BEVEL")
        bv.width = bevel
        bv.segments = segments
        bv.limit_method = "ANGLE"
        bv.angle_limit = math.radians(40)
        bv.harden_normals = True
    return obj

def link(obj):
    scene.collection.objects.link(obj)
    return obj

def profile_mesh(name, rings, depth):
    """rings: list of closed 2D loops (first outer, rest holes). Extruded along +Z by depth."""
    bm = bmesh.new()
    loops = []
    for ring in rings:
        verts = [bm.verts.new((x, y, 0)) for x, y in ring]
        loops.append(verts)
    edges = []
    for verts in loops:
        for i in range(len(verts)):
            edges.append(bm.edges.new((verts[i], verts[(i + 1) % len(verts)])))
    bmesh.ops.triangle_fill(bm, use_beauty=True, use_dissolve=True, edges=edges)
    faces = list(bm.faces)
    ext = bmesh.ops.extrude_face_region(bm, geom=faces)
    bmesh.ops.translate(bm, vec=(0, 0, depth), verts=[g for g in ext["geom"] if isinstance(g, bmesh.types.BMVert)])
    bmesh.ops.recalc_face_normals(bm, faces=bm.faces)
    me = bpy.data.meshes.new(name)
    bm.to_mesh(me)
    bm.free()
    return link(bpy.data.objects.new(name, me))

def circle(r, n=96, cx=0.0, cy=0.0, reverse=False):
    pts = [(cx + r * math.cos(2 * math.pi * i / n), cy + r * math.sin(2 * math.pi * i / n)) for i in range(n)]
    return pts[::-1] if reverse else pts

def gear_outline(z, module):
    """Approximate involute spur gear outline."""
    rp = module * z / 2
    ra = rp + module
    rf = rp - 1.25 * module
    rb = rp * math.cos(math.radians(20))
    pts = []
    pitch_ang = 2 * math.pi / z
    half_tooth = pitch_ang / 4  # tooth thickness at pitch circle = half pitch
    def inv(a):
        return math.tan(a) - a
    a_p = math.acos(rb / rp)
    for t in range(z):
        c = t * pitch_ang
        # root land
        for k in range(3):
            ang = c - pitch_ang / 2 + (pitch_ang / 2 - half_tooth - 0.18 * pitch_ang) * k / 2
            pts.append((rf * math.cos(ang), rf * math.sin(ang)))
        # rising flank (involute), from base/root to tip
        flank = []
        for k in range(7):
            r = max(rb, rf) + (ra - max(rb, rf)) * k / 6
            a = math.acos(min(1.0, rb / r))
            off = half_tooth + inv(a_p) - inv(a)
            flank.append((r, off))
        for r, off in flank:
            pts.append((r * math.cos(c - off), r * math.sin(c - off)))
        for r, off in reversed(flank):
            pts.append((r * math.cos(c + off), r * math.sin(c + off)))
        for k in range(3):
            ang = c + half_tooth + 0.18 * pitch_ang + (pitch_ang / 2 - half_tooth - 0.18 * pitch_ang) * k / 2
            pts.append((rf * math.cos(ang), rf * math.sin(ang)))
    # dedupe close points
    clean = []
    for p in pts:
        if not clean or (Vector(p) - Vector(clean[-1])).length > 1e-5:
            clean.append(p)
    return clean, rp

def gear(name, z, module, width, bore, material, spokes=0, hub=0.0):
    outline, rp = gear_outline(z, module)
    holes = [circle(bore, 48, reverse=True)]
    if spokes:
        rf = rp - 1.25 * module
        r_mid = (bore + hub + rf - module * 1.4) / 2
        r_hole = (rf - module * 1.4 - bore - hub) / 2 * 0.78
        for s in range(spokes):
            a = 2 * math.pi * s / spokes + math.pi / spokes
            holes.append(circle(r_hole, 40, r_mid * math.cos(a), r_mid * math.sin(a), reverse=True))
    obj = profile_mesh(name, [outline] + holes, width)
    return finish(obj, material, bevel=min(0.0022, module * 0.18)), rp

def cylinder(name, r, h, material, z0=0.0, n=64, bevel=0.002, hole=0.0):
    rings = [circle(r, n)] + ([circle(hole, n, reverse=True)] if hole else [])
    obj = profile_mesh(name, rings, h)
    obj.location.z = z0
    return finish(obj, material, bevel=bevel)

def rounded_rect(w, h, r, n=10):
    pts = []
    for cx, cy, a0 in ((w / 2 - r, h / 2 - r, 0), (-w / 2 + r, h / 2 - r, 90), (-w / 2 + r, -h / 2 + r, 180), (w / 2 - r, -h / 2 + r, 270)):
        for k in range(n + 1):
            a = math.radians(a0 + 90 * k / n)
            pts.append((cx + r * math.cos(a), cy + r * math.sin(a)))
    return pts

def hex_bolt(name, x, y, z, size=0.018):
    head = profile_mesh(name, [circle(size, 6)], size * 0.75)
    head.location = (x, y, z)
    head.rotation_euler.z = math.radians(15)
    finish(head, M_BLACK, bevel=0.0018)
    return head

# ---------------------------------------------------------------- build the gearbox
MOD = 0.010
A = Vector((-0.17, 0.0, 0))       # shaft A
B = Vector((0.15, 0.0, 0))        # shaft B (centre distance = (48+16)/2 * m = 0.32)

parts = []  # (obj, exploded_offset Vector, exploded_rot_z, start_frame_fraction)

root = bpy.data.objects.new("Assembly", None)
link(root)

def add(obj, offset, rot=0.0, order=0.0):
    obj.parent = root
    parts.append((obj, Vector(offset), rot, order))
    return obj

plate = profile_mesh("Housing plate", [rounded_rect(0.86, 0.54, 0.06)] + [circle(0.034, 48, A.x, A.y, True), circle(0.034, 48, B.x, B.y, True)], 0.045)
finish(plate, M_HOUSING, bevel=0.004)
add(plate, (0, 0, 0), 0, 0)

txt = bpy.data.curves.new("Etch", "FONT")
txt.body = "NORTHLINE  NL-2S  48:16 / 36:14"
txt.size = 0.026
txt.extrude = 0.0003
txt.align_x = "LEFT"
etch = link(bpy.data.objects.new("Etch", txt))
etch.location = (-0.39, -0.225, 0.0452)
etch.data.materials.append(M_BLACK)
add(etch, (0, 0, 0), 0, 0)

# Bearing housings (oxide anodized) seated in the plate.
for i, P in enumerate((A, B)):
    bh = cylinder(f"Bearing housing {i}", 0.058, 0.03, M_OXIDE, z0=0.045, hole=0.031)
    bh.location.x, bh.location.y = P.x, P.y
    add(bh, (0, 0, 0.12), 0, 0.10)
    bush = cylinder(f"Bushing {i}", 0.031, 0.034, M_BRASS, z0=0.044, hole=0.0205, bevel=0.0012)
    bush.location.x, bush.location.y = P.x, P.y
    add(bush, (0, 0, 0.2), 0, 0.16)

# Shafts.
for i, P in enumerate((A, B)):
    sh = cylinder(f"Shaft {i}", 0.0205, 0.34, M_POLISH, z0=0.02, bevel=0.0015)
    sh.location.x, sh.location.y = P.x, P.y
    add(sh, (0, 0, 0.5), 0, 0.22)

# Stage 1: 48T gear on A, 16T pinion on B.
g1, _ = gear("Gear 48T", 48, MOD, 0.042, 0.021, M_STEEL, spokes=6, hub=0.03)
g1.location = (A.x, A.y, 0.09)
add(g1, (-0.10, 0, 0.36), math.radians(-70), 0.34)

p1, _ = gear("Pinion 16T", 16, MOD, 0.052, 0.021, M_DARK)
p1.location = (B.x, B.y, 0.085)
p1.rotation_euler.z = math.pi / 16
add(p1, (0.12, 0, 0.42), math.radians(110), 0.40)

# Stage 2: 36T oxide gear on B, 14T pinion on A.
g2, _ = gear("Gear 36T", 36, MOD, 0.036, 0.021, M_OXIDE, spokes=5, hub=0.026)
g2.location = (B.x, B.y, 0.152)
add(g2, (0.14, 0, 0.54), math.radians(80), 0.52)

p2, _ = gear("Pinion 14T", 14, MOD, 0.06, 0.021, M_DARK)
p2.location = (A.x, A.y, 0.145)
add(p2, (-0.12, 0, 0.62), math.radians(-120), 0.58)

# Retaining collars and bolts.
for i, P in enumerate((A, B)):
    col = cylinder(f"Collar {i}", 0.03, 0.022, M_BLACK, z0=0.22 if i == 0 else 0.198, hole=0.0205, bevel=0.0015)
    col.location.x, col.location.y = P.x, P.y
    add(col, (0, 0, 0.5), math.radians(40), 0.70)

for i, (x, y) in enumerate(((0.37, 0.21), (-0.37, 0.21), (0.37, -0.21), (-0.37, -0.21))):
    b = hex_bolt(f"Bolt {i}", x, y, 0.045)
    add(b, (0, 0, 0.30), math.radians(180), 0.78 + i * 0.03)

# ---------------------------------------------------------------- animation
LAST = FRAMES - 1
for obj, off, rot, order in parts:
    home_loc = obj.location.copy()
    home_rot = obj.rotation_euler.z
    start = round(order * LAST * 0.62)
    end = min(LAST, start + round(LAST * 0.34))
    obj.location = home_loc + off
    obj.rotation_euler.z = home_rot + rot
    obj.keyframe_insert("location", frame=0)
    obj.keyframe_insert("rotation_euler", index=2, frame=0)
    obj.keyframe_insert("location", frame=start)
    obj.keyframe_insert("rotation_euler", index=2, frame=start)
    obj.location = home_loc
    obj.rotation_euler.z = home_rot
    obj.keyframe_insert("location", frame=end)
    obj.keyframe_insert("rotation_euler", index=2, frame=end)

# Slow turntable for parallax.
root.rotation_euler.z = math.radians(-28)
root.keyframe_insert("rotation_euler", index=2, frame=0)
root.rotation_euler.z = math.radians(14)
root.keyframe_insert("rotation_euler", index=2, frame=LAST)
for ob in [root] + [p[0] for p in parts]:
    if ob.animation_data and ob.animation_data.action:
        try:
            fcurves = ob.animation_data.action.fcurves
        except AttributeError:  # layered actions (Blender 4.4+)
            fcurves = [fc for layer in ob.animation_data.action.layers for strip in layer.strips for bag in strip.channelbags for fc in bag.fcurves]
        for fc in fcurves:
            for kp in fc.keyframe_points:
                kp.interpolation = "BEZIER"
                kp.easing = "EASE_IN_OUT"

# ---------------------------------------------------------------- stage: shadow catcher, lights, camera, world
bpy.ops.mesh.primitive_plane_add(size=6, location=(0, 0, 0))
ground = bpy.context.active_object
ground.name = "Shadow catcher"
ground.is_shadow_catcher = True

def area(name, loc, size, energy, color=(1, 1, 1)):
    d = bpy.data.lights.new(name, "AREA")
    d.size = size
    d.energy = energy
    d.color = color
    o = link(bpy.data.objects.new(name, d))
    o.location = loc
    tc = o.constraints.new("TRACK_TO")
    tc.target = root
    return o

area("Key", (-1.4, -1.2, 2.2), 1.6, 300, (1.0, 0.97, 0.93))
area("Rim", (1.6, 1.4, 1.2), 1.2, 240, (0.85, 0.92, 1.0))
area("Fill", (1.8, -1.6, 0.6), 2.0, 60)

world = bpy.data.worlds.new("World")
scene.world = world
world.use_nodes = True
env = world.node_tree.nodes.new("ShaderNodeTexEnvironment")
env.image = bpy.data.images.load(os.path.join(bpy.utils.system_resource("DATAFILES", path="studiolights/world"), "interior.exr"))
bg = world.node_tree.nodes["Background"]
bg.inputs["Strength"].default_value = 0.42
world.node_tree.links.new(env.outputs["Color"], bg.inputs["Color"])

target = bpy.data.objects.new("Camera target", None)
link(target)
target.location = (0.0, 0.0, 0.2)
cam_data = bpy.data.cameras.new("Camera")
cam_data.lens = 58
cam_data.dof.use_dof = True
cam_data.dof.focus_object = target
cam_data.dof.aperture_fstop = 5.6
cam = link(bpy.data.objects.new("Camera", cam_data))
DIR = Vector((1.38, -1.62, 1.28)).normalized()
cam.location = DIR * 2.75 + Vector((0, 0, 0.32))
target.location.z = 0.40
cam.keyframe_insert("location", frame=0)
target.keyframe_insert("location", frame=0)
cam.keyframe_insert("location", frame=round((FRAMES - 1) * 0.35))
target.keyframe_insert("location", frame=round((FRAMES - 1) * 0.35))
cam.location = DIR * 1.98
target.location.z = 0.1
cam.keyframe_insert("location", frame=FRAMES - 1)
target.keyframe_insert("location", frame=FRAMES - 1)
tc = cam.constraints.new("TRACK_TO")
tc.target = target
scene.camera = cam

# ---------------------------------------------------------------- render settings
r = scene.render
r.engine = "CYCLES"
r.resolution_x = r.resolution_y = RES
r.film_transparent = True
r.image_settings.file_format = "PNG"
r.image_settings.color_mode = "RGBA"
scene.cycles.device = "CPU"
scene.cycles.samples = SAMPLES
scene.cycles.use_adaptive_sampling = True
scene.cycles.adaptive_threshold = 0.03
scene.cycles.use_denoising = True
scene.cycles.max_bounces = 8
scene.view_settings.view_transform = "AgX"
scene.view_settings.look = "AgX - Medium High Contrast"

if SAVE:
    bpy.ops.wm.save_as_mainfile(filepath=SAVE)

os.makedirs(OUT, exist_ok=True)
if PREVIEW is not None:
    scene.frame_set(int(PREVIEW))
    r.filepath = os.path.join(OUT, f"preview_{int(PREVIEW):03d}.png")
    bpy.ops.render.render(write_still=True)
else:
    r.filepath = os.path.join(OUT, "f_")
    bpy.ops.render.render(animation=True)
