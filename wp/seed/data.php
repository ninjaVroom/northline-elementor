<?php
/**
 * Northline launch content. Seed content for the portfolio demonstration.
 * Illustrative portfolio content (DESIGN.md): people, quotes, prices and metrics are the project's own.
 * Keys are stable source IDs so the seed is idempotent.
 */

$p = fn( string ...$paras ) => implode( "\n", array_map( fn( $x ) => "<p>$x</p>", $paras ) );

return [

	'people' => [
		'martin-vance' => [
			'name' => 'Martin Vance, P.Eng.', 'rank' => 10, 'img' => 'person-01-martin-vance',
			'job'  => 'Managing Principal, Plant Operations and Lean Systems',
			'bio'  => 'Twenty-four years leading multi-plant automotive stamping, forging and heavy assembly turnarounds across Illinois, Michigan and Ontario. Former VP of Manufacturing for a $380M Tier 2 powertrain supplier.',
		],
		'claire-mercier' => [
			'name' => 'Claire Mercier, P.Eng.', 'rank' => 20, 'img' => 'person-02-claire-mercier',
			'job'  => 'Partner, Supply Chain, S&OP and Working Capital (EN/FR)',
			'bio'  => 'Industrial engineer specializing in constraint-based scheduling, inventory buffer design and plant consolidation math for PE-backed manufacturers across the Great Lakes and Quebec.',
		],
		'david-lindqvist' => [
			'name' => 'David K. Lindqvist', 'rank' => 30, 'img' => 'person-03-david-lindqvist',
			'job'  => 'Senior Operations Director, Changeover and Tooling (SMED)',
			'bio'  => 'Toolmaker by trade and mechanical engineer who has led over 140 press, extrusion and CNC changeover compressions, routinely cutting setup windows by 30% to 55% without new capital equipment.',
		],
		'sonia-patel' => [
			'name' => 'Sonia Patel', 'rank' => 40, 'img' => 'person-04-sonia-patel',
			'job'  => 'Principal, Tiered Daily Management and Floor Sustainment',
			'bio'  => 'Builds the daily management systems supervisors run after the engagement ends: shift huddles, escalation boards and leader standard work, checked in monthly floor audits until the rhythm holds without outside help.',
		],
	],

	'quotes' => [
		'q-home-stamping' => [
			'text' => 'Northline didn’t hand us a 200-slide deck. In two weeks we had a signed baseline on Line 3, and by week eleven we released $1.2M in working capital with zero unplanned layoffs.',
			'by'   => 'VP of Operations', 'org' => 'Tier 2 automotive stamping supplier', 'industry' => 'Automotive & stamping',
		],
		'q-hydraulics' => [
			'text' => 'Having Martin and David on the floor at 5:30 a.m. shift handover earned immediate credibility with our press leads.',
			'by'   => 'Plant Director', 'org' => 'Precision Hydraulics Group', 'industry' => 'Precision machining',
		],
		'q-case-stamping' => [
			'text' => 'Northline stabilized our press lines before our Q3 audit.',
			'by'   => 'VP Operations', 'org' => 'Tier 2 Stamping', 'industry' => 'Automotive & stamping',
		],
	],

	'faqs' => [
		'faq-start'    => [ 'q' => 'Where should we start?', 'topic' => 'General', 'rank' => 10, 'a' => $p( 'Most plants start with the 5-Day Plant Diagnostic. It produces a signed baseline and a 90-day roadmap, so any later sprint is scoped against numbers your plant manager has already agreed.' ) ],
		'faq-alongside'=> [ 'q' => 'Do you work alongside our team?', 'topic' => 'General', 'rank' => 20, 'a' => $p( 'Yes. The partners who scope the work are on the floor with your supervisors and operators. Standard work is built on the line with the people who will run it, not handed over in a report.' ) ],
		'faq-baseline' => [ 'q' => 'How soon will we see a baseline?', 'topic' => 'Throughput', 'rank' => 30, 'a' => $p( 'Within the first two weeks. We walk the value stream with each shift, agree the loss categories and have the plant manager sign the baseline before any change is made.' ) ],
		'faq-after'    => [ 'q' => 'What happens after the sprint?', 'topic' => 'Throughput', 'rank' => 40, 'a' => $p( 'Supervisors own the run standards and the weekly scorecard from the handoff week. Plants that want help holding the rhythm move to Tiered Daily Sustainment, with monthly floor audits.' ) ],
		'faq-fee'      => [ 'q' => 'What is included in the diagnostic fee?', 'topic' => 'Pricing', 'rank' => 50, 'a' => $p( 'Five days on your floor, a bottleneck loss tree, a 12-week payback model and a board-ready readout. Travel is billed at cost; there are no contingency fees.' ) ],
		'faq-single'   => [ 'q' => 'Can we start with a single production line?', 'topic' => 'Pricing', 'rank' => 60, 'a' => $p( 'Yes. A Targeted Floor Sprint is scoped to one value stream. Many clients prove the method on their constraint line before extending it across a site.' ) ],
		'faq-five'     => [ 'q' => 'What happens after the five days?', 'topic' => 'Campaign', 'rank' => 70, 'a' => $p( 'You keep the baseline, the loss tree and the payback model whether or not you continue. If the numbers support it, we scope a floor sprint against the constraint the review found.' ) ],
	],

	'services' => [
		'5-day-plant-diagnostic' => [
			'title' => '5-Day Plant Diagnostic', 'line' => 'Diagnostics and analysis', 'rank' => 10, 'img' => 'service-01-plant-diagnostic',
			'summary' => 'A signed baseline and a practical 90-day roadmap.',
			'overview' => $p( 'Five days on your floor, walking each value stream with the people who run it. We separate availability, performance and quality losses, find the real constraint and price the recovery before anyone asks for capital.', 'You leave with a baseline your plant manager has signed and a roadmap ordered by payback.' ),
			'deliverables' => [ [ 'Signed operating baseline', 'Agreed with the plant manager on the floor, by line and by shift.' ], [ 'Bottleneck loss tree', 'Availability, performance and quality losses at the constraint.' ], [ '12-week payback model', 'The cash and capacity case for each fix, reconciled with finance.' ], [ '90-day roadmap', 'Sequenced actions with owners, ready for a board readout.' ] ],
			'steps' => [ [ 'Walk and baselines', 'Day 1', 'Value-stream walk with each shift; agree how losses are counted.' ], [ 'Loss tree', 'Day 2', 'Separate availability, performance and quality losses at the constraint.' ], [ 'Flow and WIP', 'Day 3', 'Trace work in progress and the schedule against real capacity.' ], [ 'Economics', 'Day 4', 'Price each loss and build the 12-week payback model.' ], [ 'Board readout', 'Day 5', 'Signed baseline and the 90-day roadmap, presented on site.' ] ],
			'format' => '5 days on site, fixed fee', 'lead' => 'claire-mercier', 'faqs' => [ 'faq-start', 'faq-fee', 'faq-five' ],
		],
		'throughput-and-oee-recovery' => [
			'title' => 'Throughput and OEE Recovery', 'line' => 'Floor implementation', 'rank' => 20, 'img' => 'service-02-throughput-oee',
			'summary' => 'Find the constraint. Restore a repeatable run rate.',
			'overview' => $p( 'We work beside your team to expose bottleneck losses, stabilize the run and make each shift more predictable.', 'The work happens at the constraint: centerline settings, shift handovers and a weekly scorecard the supervisors own.' ),
			'deliverables' => [ [ 'Bottleneck telemetry audit', 'What the constraint actually does, shift by shift.' ], [ 'Centerline run standards', 'Settings and checks that keep the line at rate.' ], [ 'Shift handover cadence', 'Actions and ownership carried cleanly across shifts.' ], [ 'Weekly floor scorecard', 'Run by supervisors, reconciled with the ledger.' ] ],
			'steps' => [ [ 'Baseline walk', 'Week 1', 'Walk the constraint with each shift and sign the baseline.' ], [ 'Loss tree lock', 'Weeks 2–3', 'Agree loss categories and the order of attack.' ], [ 'Shift trials', 'Weeks 4–6', 'Trial centerlines and handovers on live shifts.' ], [ 'Supervisor handoff', 'Weeks 7–8', 'Supervisors own the standards and the scorecard.' ] ],
			'format' => '8-week embedded floor sprint', 'lead' => 'martin-vance', 'faqs' => [ 'faq-baseline', 'faq-after' ],
		],
		'smed-changeover-reduction' => [
			'title' => 'SMED Changeover Reduction', 'line' => 'Floor implementation', 'rank' => 30, 'img' => 'service-03-smed-changeover',
			'summary' => 'Cut setup time and increase capacity.',
			'overview' => $p( 'Changeover time is capacity you already own. We film the changeover, separate internal from external work and move preparation off the critical path, so presses and cells spend more of the shift making parts.', 'No new capital equipment is assumed. Die carts, staging and standard sheets come first.' ),
			'deliverables' => [ [ 'Changeover video study', 'Every element timed and classified with the crew.' ], [ 'External setup matrix', 'Preparation moved off the critical changeover path.' ], [ 'Point-of-use staging', 'Dies, tools and gauges where the crew needs them.' ], [ 'Standard changeover sheets', 'Written with the crew and posted at the machine.' ] ],
			'steps' => [ [ 'Film and time', 'Week 1', 'Record the current changeover with the crew that runs it.' ], [ 'Separate the work', 'Week 2', 'Split internal and external elements; remove waste.' ], [ 'Stage and trial', 'Weeks 3–5', 'Stage tooling at point of use and trial the new sequence.' ], [ 'Lock the standard', 'Weeks 6–8', 'Train each shift and post the standard at the machine.' ] ],
			'format' => '6–8 week floor sprint', 'lead' => 'david-lindqvist', 'faqs' => [ 'faq-single', 'faq-alongside' ],
		],
		'scheduling-and-wip-control' => [
			'title' => 'Scheduling and WIP Control', 'line' => 'Floor implementation', 'rank' => 40, 'img' => 'case-05-heavy-fabrication',
			'summary' => 'Stabilize flow and reduce lead time.',
			'overview' => $p( 'When every site schedules to its own rhythm, work in progress piles up in front of the constraint and cash sits on the floor. We rebuild daily scheduling around the constraint and size buffers so the line runs and the cash comes back.', 'Results are reconciled with your CFO, line by line, so the working-capital release shows up in the ledger.' ),
			'deliverables' => [ [ 'Constraint-based schedule', 'Daily scheduling built around the real bottleneck.' ], [ 'Buffer and WIP limits', 'Sized from demand and run data, not guesswork.' ], [ 'S&OP cadence', 'A monthly rhythm that links sales, plant and finance.' ], [ 'Working-capital ledger', 'Every claim tied to ledger impact.' ] ],
			'steps' => [ [ 'Map the flow', 'Weeks 1–2', 'Trace orders and WIP across sites and the scheduling office.' ], [ 'Set the drum', 'Weeks 3–4', 'Schedule to the constraint and size the buffers.' ], [ 'Run and adjust', 'Weeks 5–8', 'Run the new schedule live and tune buffers weekly.' ], [ 'Hand over S&OP', 'Weeks 9–10', 'Planners own the cadence; finance owns the ledger.' ] ],
			'format' => '8–10 week floor sprint', 'lead' => 'claire-mercier', 'faqs' => [ 'faq-start' ],
		],
		'tiered-daily-management' => [
			'title' => 'Tiered Daily Management', 'line' => 'Capability and sustainment', 'rank' => 50, 'img' => 'case-04-architectural-extrusion',
			'summary' => 'Build a management rhythm that holds across shifts.',
			'overview' => $p( 'Gains fade when the daily rhythm depends on consultants. We build tiered huddles, escalation paths and leader standard work with your supervisors, then audit the floor monthly until the system holds on its own.' ),
			'deliverables' => [ [ 'Tiered huddle structure', 'Line, area and plant huddles with clear escalation.' ], [ 'Leader standard work', 'What each supervisor checks, and when.' ], [ 'Shift-lead coaching', 'On the floor, during real shifts.' ], [ 'Monthly floor audits', 'A written check that the rhythm is holding.' ] ],
			'steps' => [ [ 'Design the tiers', 'Month 1', 'Agree huddle levels, metrics and escalation rules.' ], [ 'Coach the leads', 'Months 2–3', 'Run huddles alongside shift leads until they own them.' ], [ 'Audit and lock', 'Monthly', 'Floor audits confirm the cadence holds across shifts.' ] ],
			'format' => 'Monthly retainer', 'lead' => 'sonia-patel', 'faqs' => [ 'faq-after' ],
		],
	],

	'cases' => [
		'tier-2-automotive-stamping-consolidation' => [
			'title' => 'Tier 2 Automotive Stamping — Consolidation', 'industry' => 'Automotive & stamping', 'img' => 'case-01-tier2-stamping', 'date' => '2026-10-20',
			'client' => 'Tier 2 Stamping', 'services' => [ 'throughput-and-oee-recovery', 'scheduling-and-wip-control' ], 'quote' => 'q-case-stamping',
			'summary' => 'Plant consolidation for a Tier 2 stamping supplier with three sites and one shared scheduling office.',
			'metrics' => [ [ '18.4%', 'OEE gain, Line 3' ], [ '$1.2M', 'Working capital released' ], [ '11 wk', 'To first measured result' ], [ '0', 'Unplanned layoffs' ] ],
			'challenge' => $p( 'Three plants shared one scheduling office, but each ran to a different rhythm. Work in progress tied up cash while press-line interruptions slowed delivery.' ),
			'approach' => $p( 'We walked the floor with operators, agreed a common baseline and rebuilt daily scheduling around the constraint. Shift teams put standard changeovers into practice.' ),
			'outcome' => $p( 'Line 3 OEE improved 18.4%, releasing $1.2M in working capital within 11 weeks, with zero unplanned layoffs.' ),
		],
		'precision-hydraulics-machining-cell-rebalance' => [
			'title' => 'Precision Hydraulics Machining — Cell Rebalance', 'industry' => 'Precision machining', 'img' => 'case-02-precision-hydraulics', 'date' => '2026-09-28',
			'client' => 'Precision Hydraulics', 'services' => [ 'smed-changeover-reduction', 'throughput-and-oee-recovery' ], 'quote' => 'q-hydraulics',
			'summary' => 'Rebalancing machining cells for a hydraulics manufacturer where long setups and scrap held back a growing order book.',
			'metrics' => [ [ '34.0%', 'Setup time cut (SMED)' ], [ '14.2%', 'Scrap reduction' ] ],
			'challenge' => $p( 'Machining cells were loaded unevenly and long setups pushed operators to run oversized batches. Scrap rose at the end of long runs and the order book was outgrowing the cells.' ),
			'approach' => $p( 'We filmed changeovers with the crews, moved preparation off the critical path and rebalanced work across cells around the constraint machine. Shift handovers were held at the cell, at 5:30 a.m., with the press leads.' ),
			'outcome' => $p( 'Setup time fell 34.0% and scrap fell 14.2%, without new capital equipment.' ),
		],
		'beverage-canning-and-packaging-high-speed-line' => [
			'title' => 'Beverage Canning and Packaging — High-Speed Line', 'industry' => 'Food & beverage', 'img' => 'case-03-beverage-packaging', 'date' => '2026-08-30',
			'client' => 'Beverage Packaging', 'services' => [ 'smed-changeover-reduction' ],
			'summary' => 'Shortening format changeovers on a high-speed canning line so more of each shift goes to production.',
			'metrics' => [ [ '−38%', 'Changeover time' ] ],
			'challenge' => $p( 'Frequent format changes on a high-speed line meant long stops between runs. Each changeover depended on which crew was on shift.' ),
			'approach' => $p( 'We separated internal and external setup, staged change parts at point of use and wrote one standard sequence with the crews from every shift.' ),
			'outcome' => $p( 'Changeover time fell 38% and became consistent across shifts.' ),
		],
		'architectural-extrusion-die-staging-and-flow' => [
			'title' => 'Architectural Extrusion — Die Staging and Flow', 'industry' => 'Extrusion', 'img' => 'case-04-architectural-extrusion', 'date' => '2026-07-22',
			'client' => 'Architectural Extrusion', 'services' => [ 'smed-changeover-reduction', 'scheduling-and-wip-control' ],
			'summary' => 'Die staging and scheduling changes that raised press utilization at an architectural extrusion plant.',
			'metrics' => [ [ '+22%', 'Press utilization' ] ],
			'challenge' => $p( 'Presses waited on dies that were not ready, and the schedule did not account for die preparation time.' ),
			'approach' => $p( 'We set up point-of-use die staging and scheduled die preparation alongside press runs so the next die was ready before the current run ended.' ),
			'outcome' => $p( 'Press utilization rose 22%.' ),
		],
		'heavy-steel-fabrication-weld-bay-balance' => [
			'title' => 'Heavy Steel Fabrication — Weld Bay Balance', 'industry' => 'Heavy fabrication', 'img' => 'case-05-heavy-fabrication', 'date' => '2026-06-18',
			'client' => 'Heavy Steel Fabrication', 'services' => [ 'throughput-and-oee-recovery', 'tiered-daily-management' ],
			'summary' => 'Balancing weld bays around the constraint to cut cycle time in heavy fabrication.',
			'metrics' => [ [ '−19.5%', 'Cycle time' ] ],
			'challenge' => $p( 'Work queued unevenly across weld bays, and the bottleneck bay was often waiting on fit-up from upstream.' ),
			'approach' => $p( 'We balanced work across bays around the constraint, moved fit-up ahead of the bottleneck and set up daily tiered huddles to keep the balance.' ),
			'outcome' => $p( 'Cycle time fell 19.5%.' ),
		],
		'cleanroom-medical-molding-cavitation-recovery' => [
			'title' => 'Cleanroom Medical Molding — Cavitation Recovery', 'industry' => 'Medical molding', 'img' => 'case-06-cleanroom-molding', 'date' => '2026-05-12',
			'client' => 'Cleanroom Medical Molding', 'services' => [ 'smed-changeover-reduction', 'tiered-daily-management' ],
			'summary' => 'Recovering blocked cavities and tool changeover discipline in a cleanroom molding operation.',
			'metrics' => [ [ '99.1%', 'First-pass yield' ] ],
			'challenge' => $p( 'Cavities were being blocked off instead of repaired, and tool changeovers in the cleanroom varied by shift.' ),
			'approach' => $p( 'We restored cavitation through a tool repair routine, standardized cleanroom tool changeovers and added a daily quality huddle at the press.' ),
			'outcome' => $p( 'First-pass yield reached 99.1%.' ),
		],
	],

	'authors' => [
		'martin-vance'    => 'Martin Vance',
		'david-lindqvist' => 'David K. Lindqvist',
		'claire-mercier'  => 'Claire Mercier',
		'sonia-patel'     => 'Sonia Patel',
	],

	'categories' => [
		'floor-execution' => [ 'Floor Execution', 'Shop-floor measurement methods, bottleneck audits and supervisor routines tested across mid-market plants.' ],
		'smed'            => [ 'SMED', 'Changeover methods for presses, extrusion and CNC: staging, sequencing and standard work.' ],
		'scheduling'      => [ 'Scheduling', 'Scheduling to the constraint, WIP control and the working capital tied up on the floor.' ],
		'sustainment'     => [ 'Sustainment', 'Daily management, shift handovers and keeping gains after the engagement ends.' ],
	],

	'posts' => [
		'measuring-true-oee-baselines' => [
			'title' => 'Measuring True OEE Baselines Before Buying Iron', 'cat' => 'floor-execution', 'author' => 'martin-vance', 'img' => 'article-01-oee-baselines', 'date' => '2026-11-02', 'guide' => true,
			'excerpt' => 'A useful baseline starts at the machine. Record planned time, stops, cycles and good parts before deciding where to invest.',
			'body' => '<!-- wp:paragraph --><p>A useful baseline starts at the machine. Record planned production time, stops, actual cycles and good parts before deciding where to invest.</p><!-- /wp:paragraph -->'
				. '<!-- wp:heading --><h2>Count what the machine does, not what the report says</h2><!-- /wp:heading -->'
				. '<!-- wp:paragraph --><p>Before a capital request, walk the constraint with each shift and agree how stops are counted. A short stop logged as “running” on one shift and as downtime on another makes every later number argue with itself.</p><!-- /wp:paragraph -->'
				. '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Factor</th><th>Measure</th><th>Baseline</th></tr></thead><tbody><tr><td>Availability</td><td>Run time / planned time</td><td>82.0%</td></tr><tr><td>Performance</td><td>Ideal / actual cycle</td><td>91.0%</td></tr><tr><td>Quality</td><td>Good / total parts</td><td>98.0%</td></tr></tbody></table></figure><!-- /wp:table -->'
				. '<!-- wp:paragraph --><p>OEE = Availability × Performance × Quality. With the factors above, the line runs at about 73% OEE before anything is bought.</p><!-- /wp:paragraph -->'
				. '<!-- wp:heading --><h2>Sign it before you change it</h2><!-- /wp:heading -->'
				. '<!-- wp:paragraph --><p>Have the plant manager sign the baseline before the first improvement. Every later claim, in throughput or in cash, is measured against that signature.</p><!-- /wp:paragraph -->',
		],
		'point-of-use-die-staging-on-800-ton-presses' => [
			'title' => 'Point-of-Use Die Staging on 800-Ton Presses', 'cat' => 'smed', 'author' => 'david-lindqvist', 'img' => 'article-02-smed-die-staging', 'date' => '2026-10-21', 'guide' => true,
			'excerpt' => 'Most of a die change happens before the press stops. Stage the next die, the tools and the gauges where the crew needs them.',
			'body' => '<!-- wp:paragraph --><p>Most of a die change can happen before the press stops. If the next die, its tools and its gauges are still across the plant when the run ends, the press waits.</p><!-- /wp:paragraph -->'
				. '<!-- wp:heading --><h2>Move the preparation off the critical path</h2><!-- /wp:heading -->'
				. '<!-- wp:list --><ul><li>Stage the next die on a cart beside the press during the current run.</li><li>Kit clamps, shims and gauges per die, not per press.</li><li>Pre-set what can be pre-set while the press is still making parts.</li></ul><!-- /wp:list -->'
				. '<!-- wp:paragraph --><p>Write the sequence with the crew that runs it, post it at the press and time every change for the first month.</p><!-- /wp:paragraph -->',
		],
		'scheduling-around-the-constraint' => [
			'title' => 'Scheduling Around the Constraint', 'cat' => 'scheduling', 'author' => 'claire-mercier', 'img' => 'service-02-throughput-oee', 'date' => '2026-10-08',
			'excerpt' => 'A schedule that ignores the bottleneck builds WIP in front of it. Set the pace at the constraint and let the rest follow.',
			'body' => '<!-- wp:paragraph --><p>A schedule that loads every work center to capacity builds work in progress in front of the bottleneck. The constraint sets the pace of the whole plant, so it should set the schedule.</p><!-- /wp:paragraph -->'
				. '<!-- wp:heading --><h2>Three rules</h2><!-- /wp:heading -->'
				. '<!-- wp:list {"ordered":true} --><ol><li>Schedule the constraint first, in detail.</li><li>Protect it with a time buffer sized from real run data.</li><li>Release work upstream only at the pace the constraint consumes it.</li></ol><!-- /wp:list -->',
		],
		'tiered-shift-handover-routines' => [
			'title' => 'Tiered Shift Handover Routines', 'cat' => 'sustainment', 'author' => 'sonia-patel', 'img' => 'case-02-precision-hydraulics', 'date' => '2026-09-24', 'guide' => true,
			'excerpt' => 'Gains leak at shift change. A five-minute handover at the line carries actions and ownership across.',
			'body' => '<!-- wp:paragraph --><p>Gains leak at shift change. Actions started on days are forgotten on nights, and the same problem is rediscovered every morning.</p><!-- /wp:paragraph -->'
				. '<!-- wp:heading --><h2>Hold the handover at the line</h2><!-- /wp:heading -->'
				. '<!-- wp:paragraph --><p>Five minutes, standing at the constraint: what ran, what stopped, what is open and who owns it. Write it on the board, not in a spreadsheet.</p><!-- /wp:paragraph -->',
		],
		'working-capital-on-the-floor' => [
			'title' => 'Working Capital on the Floor', 'cat' => 'scheduling', 'author' => 'claire-mercier', 'img' => 'case-01-tier2-stamping', 'date' => '2026-09-10',
			'excerpt' => 'Trapped WIP is cash a CFO can count. Tie every floor change to the ledger and the conversation with finance gets shorter.',
			'body' => '<!-- wp:paragraph --><p>Work in progress is cash sitting on the floor. When scheduling drifts, it builds in front of the constraint and stays there.</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p>Tie each floor change to a line in the ledger, agree the method with finance before the work starts, and report the release the way the CFO already reads the balance sheet.</p><!-- /wp:paragraph -->',
		],
		'sustaining-the-daily-management-system' => [
			'title' => 'Sustaining the Daily Management System', 'cat' => 'sustainment', 'author' => 'sonia-patel', 'img' => 'service-01-plant-diagnostic', 'date' => '2026-08-27',
			'excerpt' => 'A daily management system holds when supervisors run it without help. Audit the rhythm, not the slides.',
			'body' => '<!-- wp:paragraph --><p>A daily management system holds when supervisors run it on their own. If the huddles stop when the consultants leave, the system was never handed over.</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p>Audit the rhythm monthly on the floor: are the huddles held, are escalations closed, do the boards match the ledger?</p><!-- /wp:paragraph -->',
		],
	],

	'options' => [
		'footer_description'  => 'Operations advisory for mid-market manufacturers',
		'header_cta'          => [ 'title' => 'Book a consultation', 'url' => '/book/', 'target' => '' ],
		'default_cta_heading' => 'Size a 30-minute partner conversation',
		'default_cta'         => [ 'title' => 'Book a consultation', 'url' => '/book/', 'target' => '' ],
		'guide_cta_heading'   => 'Run the floor checklist yourself first',
		'guide_cta'           => [ 'title' => 'Get the Operations Review Guide', 'url' => '/guides/operations-review/', 'target' => '' ],
		'org_name'            => 'Northline Operations Advisory',
		'offices'             => [ [ 'city' => 'Chicago', 'address' => '205 W Wacker Dr, Suite 1400' ], [ 'city' => 'Toronto', 'address' => '120 Adelaide St W, Suite 900' ] ],
		'phone'               => '+1 (312) 555-0148',
		'email'               => 'partners@northline.example',
		'hours'               => 'Mon–Fri 09:00–17:00 America/Toronto',
		'booking_url'         => '', // Optional external scheduler; the site has its own on /book/.
		'banner_enabled'      => 0,
		'case_archive_title'  => 'Measured floor outcomes',
		'case_archive_intro'  => 'The receipts: anonymized multi-site engagements, published without client logos.',
		'no_results'          => 'No engagements match these filters yet. Try another industry or service.',
		'search_intro'        => 'Search services, case studies, field notes and guides.',
		'notfound_title'      => 'That page isn’t on the floor schedule.',
		'notfound_text'       => 'The link may be old or mistyped. Search the site or start from one of these.',
		'resources_title'     => 'Resources and operating briefs',
		'resources_intro'     => 'Shop-floor measurement methods, bottleneck audits and supervisor routines tested across mid-market plants.',
		'form_success'        => 'Every inquiry is answered by a principal within one business day, never an SDR queue.',
	],
];
