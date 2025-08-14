<?php
// history.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kenya Elections Timeline</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(120deg, #e3f0ff 0%, #f9f9f9 100%);
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(30,80,180,0.08);
            padding: 36px 32px 28px 32px;
        }
        h1 {
            text-align: center;
            color: #1565c0;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        .subtitle {
            text-align: center;
            color: #444;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .timeline {
            position: relative;
            margin: 0;
            padding-left: 32px;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 18px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #c5e1fa;
            border-radius: 2px;
        }
        .event {
            position: relative;
            margin-bottom: 38px;
            padding-left: 48px;
            transition: background 0.2s;
        }
        .event:hover {
            background: #f3f8fd;
            border-radius: 8px;
        }
        .event .icon {
            position: absolute;
            left: -2px;
            top: 0;
            background: #fff;
            color: #1976d2;
            border: 2px solid #1976d2;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 8px rgba(21,101,192,0.07);
        }
        .event .year {
            font-weight: bold;
            color: #1976d2;
            font-size: 1.13em;
            margin-bottom: 2px;
        }
        .event .title {
            font-weight: 700;
            margin-bottom: 2px;
            color: #222;
        }
        .event .desc {
            color: #444;
            margin-bottom: 4px;
            font-size: 0.98em;
        }
        .event .links a {
            color: #388e3c;
            text-decoration: none;
            margin-right: 12px;
            font-size: 0.97em;
        }
        .event .links a:hover {
            text-decoration: underline;
        }
        .event .webtools {
            margin-top: 6px;
        }
        .event .webtools a {
            color: #1976d2;
            margin-right: 10px;
            font-size: 1em;
        }
        .event .webtools a:hover {
            text-decoration: underline;
        }
        .footer-link {
            text-align: center;
            margin-top: 36px;
            color: #888;
            font-size: 1em;
        }
        .footer-link a {
            color: #1565c0;
            text-decoration: none;
            margin-left: 6px;
        }
        .footer-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .container { padding: 14px 4px 12px 4px; }
            .timeline { padding-left: 14px; }
            .event { padding-left: 36px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa-solid fa-landmark"></i> Kenya Elections Timeline</h1>
        <div class="subtitle">
            Explore the key milestones in Kenya's democratic journey.<br>
            <span style="font-size:0.95em;">Click links for more info and use web tools for live data.</span>
        </div>
        <div class="timeline">
            <div class="event">
                <div class="icon"><i class="fa-solid fa-flag"></i></div>
                <div class="year">1963</div>
                <div class="title">First General Election</div>
                <div class="desc">
                    Kenya's first general election leads to independence. <b>Jomo Kenyatta</b> becomes Prime Minister.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/1963_Kenyan_general_election" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Wikipedia</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-user-tie"></i></div>
                <div class="year">1964</div>
                <div class="title">Republic Declared</div>
                <div class="desc">
                    Kenya becomes a republic; <b>Jomo Kenyatta</b> is the first President.
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-users"></i></div>
                <div class="year">1969 - 1988</div>
                <div class="title">One-Party Elections</div>
                <div class="desc">
                    Elections held under KANU's one-party rule (1969, 1974, 1979, 1983, 1988).
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/Kenya_African_National_Union" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> KANU</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-gavel"></i></div>
                <div class="year">1992</div>
                <div class="title">Return to Multiparty Democracy</div>
                <div class="desc">
                    First multiparty elections since independence. <b>Daniel arap Moi</b> wins amid controversy.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/1992_Kenyan_general_election" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Wikipedia</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-vote-yea"></i></div>
                <div class="year">1997</div>
                <div class="title">Second Multiparty Election</div>
                <div class="desc">
                    <b>Daniel arap Moi</b> re-elected. Opposition remains divided.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/1997_Kenyan_general_election" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Wikipedia</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-handshake"></i></div>
                <div class="year">2002</div>
                <div class="title">Historic Opposition Victory</div>
                <div class="desc">
                    <b>Mwai Kibaki</b> wins, ending KANU's 39-year rule. Peaceful transition.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/2002_Kenyan_general_election" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Wikipedia</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-fire"></i></div>
                <div class="year">2007</div>
                <div class="title">Controversial Election & Violence</div>
                <div class="desc">
                    Disputed results between <b>Mwai Kibaki</b> and <b>Raila Odinga</b> lead to violence.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/2007%E2%80%9308_Kenyan_crisis" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> 2007-08 Crisis</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-scale-balanced"></i></div>
                <div class="year">2010</div>
                <div class="title">New Constitution</div>
                <div class="desc">
                    Kenya adopts a new constitution, introducing devolution and reforms.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/Constitution_of_Kenya" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Constitution</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-person-booth"></i></div>
                <div class="year">2013</div>
                <div class="title">First Election Under New Constitution</div>
                <div class="desc">
                    <b>Uhuru Kenyatta</b> elected President. Supreme Court upholds results.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/2013_Kenyan_general_election" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Wikipedia</a>
                </div>
                <div class="webtools">
                    <a href="https://www.iebc.or.ke/" target="_blank"><i class="fa-solid fa-globe"></i> IEBC Live Results</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-balance-scale"></i></div>
                <div class="year">2017</div>
                <div class="title">Annulled Election & Repeat Poll</div>
                <div class="desc">
                    Supreme Court annuls presidential election; repeat poll held. <b>Uhuru Kenyatta</b> wins again.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/2017_Kenyan_general_election" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Wikipedia</a>
                </div>
                <div class="webtools">
                    <a href="https://www.iebc.or.ke/" target="_blank"><i class="fa-solid fa-chart-line"></i> IEBC Results</a>
                </div>
            </div>
            <div class="event">
                <div class="icon"><i class="fa-solid fa-person-chalkboard"></i></div>
                <div class="year">2022</div>
                <div class="title">Recent Election</div>
                <div class="desc">
                    <b>William Ruto</b> wins presidency. Peaceful transition, Supreme Court upholds results.
                </div>
                <div class="links">
                    <a href="https://en.wikipedia.org/wiki/2022_Kenyan_general_election" target="_blank"><i class="fa-brands fa-wikipedia-w"></i> Wikipedia</a>
                </div>
                <div class="webtools">
                    <a href="https://www.iebc.or.ke/" target="_blank"><i class="fa-solid fa-globe"></i> IEBC Official</a>
                </div>
            </div>
        </div>
        <div class="footer-link">
            <i class="fa-solid fa-link"></i>
            <a href="https://www.iebc.or.ke/" target="_blank">IEBC Official Website</a> |
            <a href="https://www.parliament.go.ke/" target="_blank">Kenya Parliament</a>
        </div>
    </div>
</body>
</html>