<?php

/**
 * Fictional product naming for the portfolio demo (not a real company).
 *
 * @see resources/js/demoBrand.js — keep in sync for Vue copy.
 *
 * Company names: funny parodies of well-known AU brands; first letter ≠ real brand.
 * Site labels: real NSW suburb names (sample geography only).
 */
return [
    'name' => 'ConnectWave',
    'tagline' => 'Cloud Voice & Connectivity',
    'desk_title' => 'Operations Desk',
    'desk_subtitle' => 'Partner & Customer Support · Faults · Provisioning · Number Ports',

    /** Bump when public/legacy/*.js or *.css change (cache-bust query on static legacy pages). */
    'legacy_asset_version' => env('DEMO_LEGACY_ASSET_VERSION', '14'),

    /** Showcase customer (Atlassian → I not A). */
    'showcase_account_name' => 'IssueTrackeroo Pty Ltd',

    /** Showcase ticket site suburb (NSW). */
    'showcase_suburb' => 'Parramatta',

    /** Port-from carriers (Telstra/Optus/Vodafone → D/M/P). */
    'fictional_carriers' => ['Dropout Mobile', 'Maybeus', 'Patchyfolk'],

    /** @var list<string> */
    'reseller_names' => [
        'Dropout Wholesale Partners',
        'Maybeus Channel Alliance',
        'Patchyfolk SIP Resellers',
        'Upside-Down Cross Telecom',
        'Emus Can\'t Fly Business Voice',
        'Shared Loot Connect Wholesale',
        'Leftfielders UC Partners',
        'Hammer Time Trade VoIP',
        'Norman? Never Heard of Her Comms',
        'Kangaroo Can Fly SIP Trunks',
    ],

    /** @var list<string> First entry = showcase_account_name. */
    'customer_names' => [
        'IssueTrackeroo Pty Ltd',
        'Groceteria Overlords HQ',
        'Budget Barn Supermarkets',
        'Waiting Room Forever Health',
        'Flipper King Franchise Ops AU',
        'Sprinter Ellison Legal',
        'Norman? Never Heard of Her Retail IT',
        'Tripped Centre Travel',
        'Hammer Time Warehouse Ops',
        'Snail Mail Express',
        'Shared Loot Contractors Voice',
        'Lab Coat Optional Research',
        'Diploma Mill of Penrith',
        'Stationery Station Supplies',
        'Hi-Def Sisters Audio Group',
        'Tablet Hoarder Chemist HQ',
        'Department of Confusion Retail',
        'Rock Bottom Mining Comms',
        'Fortune-Less Metals Pty Ltd',
        'TimTammy Knockoff Biscuits HQ',
        'Kitten Not Lion Beverages',
        'Kangaroo Can Fly Lounge Phones',
        'Gaslight Energy Stores',
        'Bars-of-None Retail Kiosks',
        'Can\'t ABank Branch Services',
        'Eastpac Banking Voice',
        'Talent Peep HR Line',
        'Homely Listings Call Centre',
        'Drawpad Design Pty Ltd',
        'Buy Now Cry Later IVR',
    ],

    /** @var list<string> NSW suburbs for service line labels. */
    /*
     * HR / application portfolio (see /portfolio).
     * Override URLs in .env: DEMO_PORTFOLIO_LIVE_URL, DEMO_PORTFOLIO_AUTHOR_SITE
     */
    'portfolio' => [
        'author' => 'Jonathan Rapusas',
        'author_site' => env('DEMO_PORTFOLIO_AUTHOR_SITE', 'https://jorap.com'),
        'live_url' => env('DEMO_PORTFOLIO_LIVE_URL', env('APP_URL', 'http://127.0.0.1:8000')),
        'page_title' => 'HR review guide — live work sample',
        'elevator' => 'Jonathan Rapusas is a senior PHP developer with 14+ years on Australian business hours at TPG Telecom (internal tools, large MySQL databases, legacy PHP) and about four years as a contractor for a confidential U.S. digital agency (names and client work are not public because of NDA). He built this live sample because employer code cannot be shared, and deployed it on his own PHP hosting so HR and hiring managers can open a real link, not localhost only.',
        'summary' => 'ConnectWave is a made-up Australian cloud-voice operations desk. It looks like an internal support portal: ticket queues, corporate bids, partner accounts, and thousands of sample records. A modern browser desk, a JSON API, and older PHP and jQuery screens all use the same database. That is a common pattern in telecom and BPO client teams that are half-upgraded.',
        'surfaces' => [
            ['href' => '/', 'label' => 'Main operations desk (start here)', 'note' => 'Best link for HR: dashboard, support queue, bids, provisioning. Typical portal layout for a telecom-style client.'],
            ['href' => '/legacy/admin.html', 'label' => 'Legacy jQuery admin', 'note' => 'Optional: older UI on the same live data. Good signal for “50/50 modern and legacy” roles.'],
            ['href' => '/legacy/ticket_summary.html', 'label' => 'Legacy PHP report', 'note' => 'Optional: simple report page. Hiring manager can open; HR can skip if time is short.'],
            ['href' => '/api/v1/dashboard', 'label' => 'API dashboard (raw JSON)', 'note' => 'For technical interviewers only. HR can ignore unless the job ad stresses REST APIs.'],
        ],
        'commercial_work' => [
            [
                'role' => 'TPG Telecom',
                'tenure' => '14+ years · Australian business hours',
                'stack' => 'PHP (CodeIgniter), MySQL, jQuery, internal APIs',
                'contribution' => 'Owned and extended internal tools for corporate bidding, pricing, provisioning tracking, and reporting on large MySQL datasets.',
                'relevance' => 'Closest match to many telecom and BPO client roles: legacy PHP, long-lived systems, AU timezone, portal-style workflows. Source code is not public; this demo shows the same job shapes on a modern stack.',
            ],
            [
                'role' => 'Confidential U.S. digital agency',
                'tenure' => '~4 years · independent contractor',
                'stack' => 'WordPress, PHP, MySQL, HTML/CSS/JS, integrations, hosting; Cursor AI (2+ years), MCP servers, custom agent skills and rules',
                'contribution' => 'Delivered 21 full website builds with custom PHP, integrations, and production fixes under tight marketing launch deadlines. Used Cursor with MCPs and repo skills/rules so agent-assisted work stayed consistent and reviewable—not unsupervised copy-paste.',
                'relevance' => 'Shows recent full-stack delivery and ownership after TPG. Agency name and client URLs are not online (NDA); can be confirmed privately in hiring. WordPress was the agency stack, not the target stack in every role; pace, PHP/JS depth, and reviewed AI-assisted habits still matter for busy teams.',
            ],
        ],
        'review_steps' => [
            'Open the main desk link. Note the dashboard totals and three areas: support, bids, provisioning.',
            'Support queue: open any ticket, change status, and add the required note (audit trail).',
            'Optional: open corporate bid BQ-SHOW01, then Provisioning for a linked order.',
            'Optional: open legacy jQuery admin or legacy PHP report if the role mentions legacy PHP or jQuery.',
        ],
    ],

    'nsw_suburbs' => [
        'Parramatta',
        'Penrith',
        'Newcastle',
        'Wollongong',
        'Gosford',
        'Liverpool',
        'Chatswood',
        'Hornsby',
        'Blacktown',
        'Campbelltown',
        'Dubbo',
        'Tamworth',
        'Byron Bay',
        'Katoomba',
        'Bondi Junction',
        'Mascot',
        'Ryde',
        'Miranda',
        'Orange',
        'Bathurst',
    ],
];
