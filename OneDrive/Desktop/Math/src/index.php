<?php
require 'functions.php';
require 'Aws/config.php';

// 1. Fetch the student ID from cookies
$studentId = isset($_COOKIE['CurStudentID']) ? $_COOKIE['CurStudentID'] : null;

$gameData = null; // Default empty state
$partitionKey = null;

if ($studentId) {
    // 2. Create the partition key (e.g., 50330691_1)
    $partitionKey = $studentId . '_1';

    // 3. Fetch existing game data from DynamoDB
    try {
        // Assuming $client is initialized in Aws/config.php
        $result = $client->getItem([
            'TableName' => 'vibe_coding_dev',
            'Key' => [
                'StudentID' => ['S' => $partitionKey]
            ]
        ]);

        if (isset($result['Item']['GameData']['S'])) {
            $gameData = $result['Item']['GameData']['S'];
        }
    } catch (Exception $e) {
        // Handle error quietly or log it
        error_log("DynamoDB GetItem Error: " . $e->getMessage());
    }
}

// 4. Handle POST requests to Save Progress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $studentId) {
    $inputData = file_get_contents('php://input');
    $decodedData = json_decode($inputData, true);
    
    if (isset($decodedData['action']) && $decodedData['action'] === 'save_progress') {
        try {
            $client->putItem([
                'TableName' => 'vibe_coding_dev',
                'Item' => [
                    'StudentID' => ['S' => $partitionKey],
                    'GameData'  => ['S' => json_encode($decodedData['gameData'])]
                ]
            ]);
            echo json_encode(["status" => "success"]);
            exit;
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Math Kingdom Adventure</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">

</head>

<body>

    <!-- ===== Start Screen ===== -->
    <div id="start-screen" class="screen active">
        <h1>Math Kingdom Adventure ⚡</h1>
        <p>Use your math skills to zap monsters and save the kingdom!</p>
        <button id="start-btn">Start Adventure ⚔️</button>
    </div>

    <!-- ===== Hero Select Screen ===== -->
    <div id="hero-select-screen" class="screen">
        <h2 class="text-shadow" style="margin-top:2rem;">Choose Your Hero 🛡️</h2>
        <div class="hero-roster">
            <!-- Hero Option 1 -->
            <div class="hero-option selected"
                data-hero="https://raw.githubusercontent.com/Dhanush127528/images/main/hero1.png" data-name="Super Boy">
                <img src="https://raw.githubusercontent.com/Dhanush127528/images/main/hero1.png" alt="Super Boy">
                <h3 class="hero-option-name">Super Boy</h3>
                <p>Fast & agile</p>
            </div>

            <!-- Hero Option 2 -->
            <div class="hero-option" data-hero="https://raw.githubusercontent.com/Dhanush127528/images/main/hero2.png"
                data-name="Nature Mage">
                <img src="https://raw.githubusercontent.com/Dhanush127528/images/main/hero2.png" alt="Nature Mage">
                <h3 class="hero-option-name">Nature Mage</h3>
                <p>Wise & powerful</p>
            </div>
            <!-- Hero Option 3 -->
            <div class="hero-option locked" id="hero-opt-3"
                data-hero="https://raw.githubusercontent.com/Dhanush127528/images/main/hero3.png"
                data-name="Shadow Ninja" style="opacity: 0.6; filter: grayscale(1);">
                <div class="hero-lock-overlay"
                    style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:4rem; z-index:10; background: rgba(0,0,0,0.5); border-radius:12px;">
                    🔒</div>
                <img src="https://raw.githubusercontent.com/Dhanush127528/images/main/hero3.png" alt="Shadow Ninja">
                <h3 class="hero-option-name">Shadow Ninja</h3>
                <p id="hero-req-3" style="color:#ff9a9e">Clear Level 2.3</p>
            </div>

            <!-- Hero Option 4 -->
            <div class="hero-option locked" id="hero-opt-4"
                data-hero="https://raw.githubusercontent.com/Dhanush127528/images/main/hero4.png"
                data-name="Cosmic Knight" style="opacity: 0.6; filter: grayscale(1);">
                <div class="hero-lock-overlay"
                    style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:4rem; z-index:10; background: rgba(0,0,0,0.5); border-radius:12px;">
                    🔒</div>
                <img src="https://raw.githubusercontent.com/Dhanush127528/images/main/hero4.png" alt="Cosmic Knight">
                <h3 class="hero-option-name">Cosmic Knight</h3>
                <p id="hero-req-4" style="color:#ff9a9e">Clear Level 3.3</p>
            </div>
        </div>
        <button id="confirm-hero-btn" class="btn-result btn-next cinematic-btn" style="margin-top:2rem;">Confirm Hero
            ➡️</button>
    </div>

    <!-- ===== Final Score Screen ===== -->
    <div id="final-score-screen" class="screen">
        <h2>👑 Kingdom Saved 👑</h2>
        <div class="scorecard-container" id="scorecard-list">
            <!-- Rows dynamically injected -->
        </div>
        <button id="btn-back-to-map" class="btn-result btn-next" style="margin-top:2rem;">Back to Map 🗺️</button>
    </div>

    <!-- ===== Map Screen ===== -->
    <div id="map-screen" class="screen">
        <button id="btn-final-scorecard" class="btn-primary cinematic-btn" style="position:absolute; bottom:40px; left:50%; transform:translateX(-50%); z-index:100; display:none; padding:15px 30px; font-size:1.2rem; box-shadow: 0 0 20px rgba(67, 233, 123, 0.6); animation: glow-pulse 2s infinite;">
            🏆 View Final Scorecard
        </button>
        <button id="btn-choose-hero" class="btn-map-action"
            style="position:absolute; top:20px; right:20px; z-index:100; font-size: 0.9rem; padding: 6px 15px 6px 6px; display:flex; align-items:center; gap:10px;">
            <img id="btn-hero-icon" src="https://raw.githubusercontent.com/Dhanush127528/images/main/hero1.png"
                style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid rgba(255, 210, 0, 0.6); object-fit: cover; background: #1a1a2e;"
                alt="Hero">
            <strong style="letter-spacing: 1px;">Change Hero</strong>
        </button>
        <div class="vmap-header">
            <h2>⚔️ Math Kingdom</h2>
        </div>
        <div class="vmap-scroll" id="hmap-scroll">
            <canvas id="path-canvas"></canvas>
            <div class="vmap-world" id="hmap-world">

                <!-- ── Domain: Ratios ── -->
                <div class="domain-card">
                    <div class="domain-card-title">📐 Ratios &amp; Proportional</div>
                    <div class="domain-card-nodes" style="height: 400px;">
                        <!-- Node 0 - left -->
                        <div class="hmap-nwrap" style="left:15%; top:20px;" id="mnw-0">
                            <img class="map-monster flip"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster1.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-0" data-level="0"><span class="adv-nn">1</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">1.1 Expressing Ratios</div>
                        </div>
                        <!-- Node 1 - right -->
                        <div class="hmap-nwrap" style="left:60%; top:150px;" id="mnw-1">
                            <img class="map-monster"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster2.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-1" data-level="1"><span class="adv-nn">2</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">1.2 Unit Rates</div>
                        </div>
                        <!-- Node 2 - left -->
                        <div class="hmap-nwrap" style="left:15%; top:280px;" id="mnw-2">
                            <img class="map-monster flip"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster3.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-2" data-level="2"><span class="adv-nn">3</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">1.3 Finding Percent</div>
                        </div>
                    </div>
                </div>

                <!-- ── Domain: Number System ── -->
                <div class="domain-card">
                    <div class="domain-card-title">🔢 The Number System</div>
                    <div class="domain-card-nodes" style="height: 400px;">
                        <!-- Node 3 - right -->
                        <div class="hmap-nwrap" style="left:60%; top:20px;" id="mnw-3">
                            <img class="map-monster"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster4.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-3" data-level="3"><span class="adv-nn">4</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">2.1 Division of Fractions</div>
                        </div>
                        <!-- Node 4 - left -->
                        <div class="hmap-nwrap" style="left:15%; top:150px;" id="mnw-4">
                            <img class="map-monster flip"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster5.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-4" data-level="4"><span class="adv-nn">5</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">2.2 Division of Whole Nos.</div>
                        </div>
                        <!-- Node 5 - right -->
                        <div class="hmap-nwrap" style="left:60%; top:280px;" id="mnw-5">
                            <img class="map-monster"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster6.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-5" data-level="5"><span class="adv-nn">6</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">2.3 Operations w/ Decimals</div>
                        </div>
                    </div>
                </div>

                <!-- ── Domain: Geometry ── -->
                <div class="domain-card">
                    <div class="domain-card-title">📏 Geometry</div>
                    <div class="domain-card-nodes" style="height: 400px;">
                        <!-- Node 6 - left -->
                        <div class="hmap-nwrap" style="left:15%; top:20px;" id="mnw-6">
                            <img class="map-monster flip"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster7.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-6" data-level="6"><span class="adv-nn">7</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">3.1 Area of Trapezoids</div>
                        </div>
                        <!-- Node 7 - right -->
                        <div class="hmap-nwrap" style="left:60%; top:150px;" id="mnw-7">
                            <img class="map-monster"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster8.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-7" data-level="7"><span class="adv-nn">8</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">3.2 Area</div>
                        </div>
                        <!-- Node 8 - left -->
                        <div class="hmap-nwrap" style="left:15%; top:280px;" id="mnw-8">
                            <img class="map-monster flip"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster9.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-8" data-level="8"><span class="adv-nn">9</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">3.3 Surface Area &amp; Vol.</div>
                        </div>
                    </div>
                </div>

                <!-- ── Domain: Expressions ── -->
                <div class="domain-card">
                    <div class="domain-card-title">✏️ Expressions &amp; Equations</div>
                    <div class="domain-card-nodes" style="height: 400px;">
                        <!-- Node 9 - right -->
                        <div class="hmap-nwrap" style="left:60%; top:20px;" id="mnw-9">
                            <img class="map-monster"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster10.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-9" data-level="9"><span class="adv-nn">10</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">4.1 Whole Number Exp.</div>
                        </div>
                        <!-- Node 10 - left -->
                        <div class="hmap-nwrap" style="left:15%; top:150px;" id="mnw-10">
                            <img class="map-monster flip"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster11.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-10" data-level="10"><span class="adv-nn">11</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">4.2 Expressions</div>
                        </div>
                        <!-- Node 11 - right -->
                        <div class="hmap-nwrap" style="left:60%; top:280px;" id="mnw-11">
                            <img class="map-monster"
                                src="https://raw.githubusercontent.com/Dhanush127528/images/main/monster12.png"
                                alt="monster">
                            <div class="adv-node locked" id="mnode-11" data-level="11"><span class="adv-nn">12</span>
                                <div class="adv-nlock">🔒</div>
                            </div>
                            <div class="adv-nl">4.3 Final Challenge</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- Animated background layers -->
        <div class="map-bg-stars"></div>
        <div class="map-bg-mountains"></div>
        <div class="map-bg-volcanoes"></div>
        <div class="map-bg-lava"></div>
        <div class="map-bg-fire-particles">
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="map-bg-magic-dust">
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="hmap-legend"><strong>Proficiency:</strong> <span class="leg-a">A</span> Advanced <span
                class="leg-p">P</span> Proficient <span class="leg-pp">PP</span> Partial</div>
    </div>
    <!-- ===== Battle Screen ===== -->
    <div id="battle-screen" class="screen">

        <button id="btn-back" class="btn-back" title="Run Away" onclick="runAway()">🚪</button>

        <!-- Glowing HUD -->
        <div class="rpg-hud">
            <!-- Hero HUD -->
            <div class="hud-panel hero-hud">
                <div class="hud-avatar">⚔️</div>
                <div class="hud-stats">
                    <div class="hud-name">HERO</div>
                    <div class="hp-track">
                        <div id="player-hp-bar" class="hp-fill"></div>
                    </div>
                </div>
                <div id="player-hp-text" class="hp-text">100/100</div>
            </div>

            <!-- Center Score/Combo -->
            <div class="hud-center">
                <div class="neon-score">SCORE <br> <span id="score-display">0</span></div>
                <div class="neon-combo">COMBO <br> x<span id="combo-display">0</span></div>
            </div>

            <!-- Monster HUD -->
            <div class="hud-panel monster-hud">
                <div id="monster-hp-text" class="hp-text">100/100</div>
                <div class="hud-stats text-right">
                    <div class="hud-name" id="hud-monster-name">Monster</div>
                    <div class="hp-track right-align">
                        <div id="monster-hp-bar" class="hp-fill monster-fill"></div>
                    </div>
                </div>
                <div class="hud-avatar">👹</div>
            </div>
        </div>

        <!-- Atmospheric Layers -->
        <div class="bg-layer"></div>
        <div class="fog-layer"></div>
        <div class="dust-particles"></div>
        <div class="vignette-overlay"></div>

        <!-- Arena -->
        <div class="battle-arena">
            <!-- Hero -->
            <div id="hero-area" class="character-area">
                <img id="hero-sprite" src="https://raw.githubusercontent.com/Dhanush127528/images/main/hero1.png"
                    alt="Hero">
                <div class="platform hero-platform"></div>
                <h3 id="hero-name" class="electric-text">Hero</h3>
            </div>

            <!-- Center feedback -->
            <div class="vs-area">
                <div id="feedback-message" class="feedback-msg"></div>
            </div>

            <!-- Monster -->
            <div id="monster-area" class="character-area">
                <img id="monster-sprite" src="./monster1.png" alt="Monster">
                <div class="platform monster-platform"></div>
                <h3 id="monster-name">Monster</h3>
            </div>

            <!-- Floating damage numbers go here (injected by JS) -->
        </div>

        <!-- Question Card -->
        <div class="question-card">
            <div id="standard-badge"
                style="position:absolute; top:10px; right:15px; background:rgba(67, 233, 123, 0.2); border:1px solid #43e97b; color:#43e97b; padding:2px 8px; border-radius:12px; font-size:0.8rem; font-weight:bold; letter-spacing:1px; display:none;">
                6.RP.A.1</div>
            <h3 id="question-text">Question goes here?</h3>
            <div id="options-container"></div>
        </div>

        <!-- Explanation Modal -->
        <div id="explanation-modal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <h2 id="explanation-title">Correct!</h2>
                <p id="explanation-body">Explanation text goes here.</p>
                <button id="btn-next-encounter" class="btn-result btn-next cinematic-btn"
                    onclick="continueAfterExplanation()">Next Question ➡️</button>
            </div>
        </div>

        <!-- Exit Confirm Modal -->
        <div id="exit-confirm-modal" class="modal-overlay"
            style="display:none; z-index: 10000; align-items: center; justify-content: center; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(5px);">
            <div class="modal-content"
                style="background: rgba(15, 20, 30, 0.95); border: 2px solid #ff5f6d; border-radius: 15px; padding: 2rem; max-width: 500px; text-align: center; box-shadow: 0 0 40px rgba(255, 95, 109, 0.4);">
                <h2
                    style="color: #ff5f6d; font-size: 2rem; margin-bottom: 1rem; text-shadow: 0 0 10px rgba(255,95,109,0.8);">
                    Run Away?</h2>
                <p style="color: #fff; font-size: 1.2rem; margin-bottom: 2rem;">Are you sure you want to run away? Your
                    battle progress will be lost!</p>
                <div style="display: flex; gap: 1.5rem; justify-content: center;">
                    <button class="btn-result btn-restart" onclick="confirmRunAway(true)">Yes, Run Away</button>
                    <button class="btn-result btn-next" onclick="confirmRunAway(false)">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Result Screen ===== -->
    <div id="result-screen" class="screen">
        <h1 id="result-title" class="animate-bounce">Level Complete! 🏆</h1>
        <div class="result-card">
            <h2>Score: <span id="final-score" class="text-primary glow-text">0</span></h2>
            <h3>Accuracy: <span id="final-accuracy" class="text-info glow-text">0%</span></h3>
            <h3>Rank: <span id="final-rank" class="text-warning glow-text">Adventurer</span></h3>
            <p id="result-message" style="margin-top:0.5rem; opacity:0.85;">Great job!</p>
        </div>
        <div class="result-buttons">
            <button id="next-level-btn" class="btn-result btn-next cinematic-btn">Next Level ➡️</button>
            <button id="restart-btn" class="btn-result btn-restart cinematic-btn">Restart 🔄</button>
        </div>
    </div>

    <!-- ===== Audio Elements ===== -->
    <audio id="sfx-correct" src="https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3"></audio>
    <audio id="sfx-wrong" src="https://assets.mixkit.co/active_storage/sfx/314/314-preview.mp3"></audio>
    <audio id="sfx-hit" src="https://assets.mixkit.co/active_storage/sfx/212/212-preview.mp3"></audio>
    <audio id="sfx-shoot" src="https://assets.mixkit.co/active_storage/sfx/2170/2170-preview.mp3"></audio>
    <audio id="sfx-win" src="https://assets.mixkit.co/active_storage/sfx/1435/1435-preview.mp3"></audio>
    <audio id="sfx-lose" src="https://assets.mixkit.co/active_storage/sfx/312/312-preview.mp3"></audio>

    <style>
        :root {
            --primary-color: #ffd200;
            /* Gold */
            --secondary-color: #ff9a9e;
            --success-color: #43e97b;
            --danger-color: #ff5f6d;
            --dark-forest: #0b1d1f;
            --midnight-blue: #09121a;
            --hud-bg: rgba(10, 18, 25, 0.85);
            --hud-border: rgba(67, 233, 123, 0.4);
            --text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            --neon-glow: 0 0 10px rgba(67, 233, 123, 0.6), 0 0 20px rgba(67, 233, 123, 0.4);
        }

        /* ===================== GLOBAL ===================== */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Fredoka', sans-serif;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            overflow: hidden;
            height: 100vh;
            width: 100vw;
        }

        .screen {
            display: none;
            height: 100vh;
            width: 100vw;
            position: absolute;
            top: 0;
            left: 0;
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .screen.active {
            display: flex;
            flex-direction: column;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.97);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* ===================== START SCREEN ===================== */
        #start-screen {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        #start-screen h1 {
            color: #fff;
            font-size: clamp(2rem, 5vw, 3.5rem);
            text-shadow: 0 0 30px rgba(79, 172, 254, 0.8), 0 0 60px rgba(79, 172, 254, 0.4);
            animation: glow-pulse 3s ease-in-out infinite;
        }

        #start-screen p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            margin: 1rem 0 2rem;
        }

        #start-btn {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            border: none;
            color: #fff;
            font-family: 'Fredoka', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            padding: 1rem 3rem;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(67, 233, 123, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
            animation: float 3s ease-in-out infinite;
        }

        #start-btn:hover {
            transform: scale(1.08) translateY(-4px);
            box-shadow: 0 20px 40px rgba(67, 233, 123, 0.6);
        }

        @keyframes glow-pulse {

            0%,
            100% {
                text-shadow: 0 0 30px rgba(79, 172, 254, 0.8), 0 0 60px rgba(79, 172, 254, 0.4);
            }

            50% {
                text-shadow: 0 0 50px rgba(79, 172, 254, 1), 0 0 90px rgba(79, 172, 254, 0.7);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        /* ===================== HERO SELECT SCREEN ===================== */
        #hero-select-screen {
            background: linear-gradient(160deg, #1f1c2c, #928dab);
            padding: 1.5rem;
            justify-content: flex-start;
            align-items: center;
            overflow-y: auto;
        }

        #hero-select-screen h2 {
            color: #fff;
            font-size: 2.5rem;
            text-shadow: var(--text-shadow);
            margin-bottom: 2rem;
            text-align: center;
        }

        .hero-roster {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 900px;
        }

        .hero-option {
            background: rgba(20, 30, 48, 0.8);
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            width: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
        }

        .hero-option::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(transparent, transparent, transparent, #ffd200);
            animation: rotateBG 4s linear infinite;
            opacity: 0;
            transition: opacity 0.3s;
        }

        @keyframes rotateBG {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .hero-option img {
            height: 120px;
            object-fit: contain;
            filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.5));
            transition: transform 0.3s;
            position: relative;
            z-index: 2;
        }

        .hero-option-name {
            color: #fff;
            margin-top: 1rem;
            font-size: 1.3rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
            position: relative;
            z-index: 2;
        }

        .hero-option p {
            color: #ccc;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .hero-option:hover {
            transform: translateY(-10px);
            border-color: #4facfe;
            box-shadow: 0 15px 30px rgba(79, 172, 254, 0.4);
        }

        .hero-option:hover img {
            transform: scale(1.1);
        }

        .hero-option.selected {
            border-color: #ffd200;
            background: rgba(40, 50, 70, 0.9);
            box-shadow: 0 0 30px rgba(255, 210, 0, 0.6);
            transform: scale(1.05) translateY(-5px);
        }

        .hero-option.selected::before {
            opacity: 0.15;
        }

        .hero-option.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #43e97b;
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
            z-index: 10;
        }

        /* ===================== MAP SCREEN - DARK FANTASY VERTICAL ===================== */
        #map-screen {
            background:
                radial-gradient(ellipse at 20% 20%, rgba(60, 0, 80, 0.5) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(0, 60, 30, 0.5) 0%, transparent 50%),
                linear-gradient(180deg, #080012 0%, #0a1a08 40%, #060a18 100%);
            overflow: hidden;
            flex-direction: column;
        }

        .vmap-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            text-align: center;
            padding: 1rem 0 0.5rem;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.85) 60%, transparent 100%);
            pointer-events: none;
        }

        .vmap-header h2 {
            color: #ffd200;
            font-size: 1.8rem;
            text-shadow: 0 0 20px rgba(255, 210, 0, 0.8), 0 2px 8px rgba(0, 0, 0, 0.9);
            letter-spacing: 2px;
        }

        .vmap-header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .vmap-scroll {
            position: relative;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 210, 0, 0.4) transparent;
        }

        .vmap-world {
            position: relative;
            width: 100%;
            min-height: 2600px;
        }

        #path-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            pointer-events: none;
            z-index: 3;
        }

        .domain-card {
            background: rgba(15, 20, 35, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 210, 0, 0.2);
            border-radius: 20px;
            margin: 60px auto;
            width: 85%;
            max-width: 800px;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.7), inset 0 0 20px rgba(255, 210, 0, 0.05);
            padding-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .domain-card-title {
            background: linear-gradient(90deg, transparent, rgba(255, 210, 0, 0.3), transparent);
            color: #ffd200;
            text-align: center;
            padding: 1rem;
            font-size: 1.5rem;
            font-weight: 800;
            border-bottom: 1px solid rgba(255, 210, 0, 0.3);
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 0 10px rgba(255, 210, 0, 0.8);
            margin-bottom: 2rem;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .domain-card-nodes {
            position: relative;
            width: 100%;
        }

        .btn-map-action {
            background: rgba(15, 20, 35, 0.85);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 210, 0, 0.4);
            color: #ffd200;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.6), inset 0 0 10px rgba(255, 210, 0, 0.1);
        }

        .btn-map-action:hover {
            background: rgba(25, 30, 45, 0.95);
            border-color: rgba(255, 210, 0, 0.8);
            box-shadow: 0 0 20px rgba(255, 210, 0, 0.4), inset 0 0 15px rgba(255, 210, 0, 0.2);
            transform: scale(1.05);
        }

        /* ===== ANIMATED MAP BACKGROUND ===== */
        .map-bg-stars,
        .map-bg-mountains,
        .map-bg-volcanoes,
        .map-bg-lava,
        .map-bg-fire-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        /* Twinkling stars */
        .map-bg-stars {
            background-image:
                radial-gradient(1px 1px at 10% 15%, rgba(255, 255, 255, 0.9) 0%, transparent 100%),
                radial-gradient(1px 1px at 30% 5%, rgba(255, 255, 255, 0.7) 0%, transparent 100%),
                radial-gradient(1.5px 1.5px at 50% 20%, rgba(255, 255, 255, 0.8) 0%, transparent 100%),
                radial-gradient(1px 1px at 70% 8%, rgba(255, 255, 255, 0.6) 0%, transparent 100%),
                radial-gradient(1px 1px at 85% 18%, rgba(255, 220, 100, 0.8) 0%, transparent 100%),
                radial-gradient(1px 1px at 20% 35%, rgba(255, 255, 255, 0.5) 0%, transparent 100%),
                radial-gradient(2px 2px at 60% 12%, rgba(255, 255, 255, 0.9) 0%, transparent 100%),
                radial-gradient(1px 1px at 45% 30%, rgba(200, 200, 255, 0.7) 0%, transparent 100%),
                radial-gradient(1px 1px at 90% 25%, rgba(255, 255, 255, 0.6) 0%, transparent 100%),
                radial-gradient(1px 1px at 15% 50%, rgba(255, 255, 255, 0.4) 0%, transparent 100%);
            animation: starTwinkle 4s ease-in-out infinite alternate;
        }

        @keyframes starTwinkle {
            0% {
                opacity: 0.6;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.5;
            }
        }

        /* Dark mountain silhouettes */
        .map-bg-mountains {
            background:
                /* Far mountains */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 300'%3E%3Cpath d='M0,300 L0,200 L80,120 L160,190 L260,80 L360,160 L440,60 L520,140 L600,90 L700,170 L800,50 L900,150 L1000,100 L1100,160 L1200,70 L1300,140 L1440,100 L1440,300 Z' fill='%230a0516'/%3E%3C/svg%3E") no-repeat bottom / 100% auto,
                /* Near mountains */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 220'%3E%3Cpath d='M0,220 L0,180 L100,140 L180,170 L280,100 L380,155 L460,80 L550,130 L660,85 L760,140 L860,60 L950,130 L1050,90 L1150,150 L1250,80 L1360,130 L1440,100 L1440,220 Z' fill='%23060312'/%3E%3C/svg%3E") no-repeat bottom / 100% auto;
            bottom: 0;
            top: auto;
            height: 50%;
        }

        /* Volcano peaks with glowing craters */
        .map-bg-volcanoes {
            background:
                /* Volcano 1 left */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 400'%3E%3Cpath d='M0,400 L80,200 L120,200 L200,400 Z' fill='%23150818'/%3E%3Cellipse cx='100' cy='198' rx='22' ry='8' fill='%23ff4500' opacity='0.8'/%3E%3Cellipse cx='100' cy='195' rx='14' ry='6' fill='%23ff6a00' opacity='0.9'/%3E%3C/svg%3E") no-repeat left bottom / 200px auto,
                /* Volcano 2 right */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 400'%3E%3Cpath d='M0,400 L80,150 L130,150 L200,400 Z' fill='%23150818'/%3E%3Cellipse cx='105' cy='148' rx='26' ry='9' fill='%23ff3000' opacity='0.85'/%3E%3Cellipse cx='105' cy='144' rx='16' ry='6' fill='%23ff6600' opacity='0.9'/%3E%3C/svg%3E") no-repeat right bottom / 250px auto;
            bottom: 0;
            top: auto;
            height: 70%;
            animation: volcanoGlow 2s ease-in-out infinite alternate;
        }

        @keyframes volcanoGlow {
            0% {
                filter: brightness(0.8) hue-rotate(0deg);
            }

            100% {
                filter: brightness(1.3) hue-rotate(10deg);
            }
        }

        /* Lava river at the bottom */
        .map-bg-lava {
            height: 60px;
            top: auto;
            bottom: 40px;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(180, 30, 0, 0.5) 15%,
                    rgba(255, 80, 0, 0.7) 30%,
                    rgba(255, 140, 0, 0.8) 50%,
                    rgba(255, 80, 0, 0.7) 70%,
                    rgba(180, 30, 0, 0.5) 85%,
                    transparent 100%);
            filter: blur(8px);
            animation: lavaFlow 3s ease-in-out infinite alternate;
        }

        @keyframes lavaFlow {
            0% {
                transform: scaleY(0.8) translateX(-5px);
                opacity: 0.6;
            }

            100% {
                transform: scaleY(1.2) translateX(5px);
                opacity: 0.9;
            }
        }

        /* Rising fire particles */
        .map-bg-fire-particles {
            overflow: hidden;
        }

        .map-bg-fire-particles span {
            position: absolute;
            bottom: 50px;
            border-radius: 50% 50% 20% 20%;
            animation: fireRise linear infinite;
            opacity: 0;
        }

        .map-bg-fire-particles span:nth-child(1) {
            left: 5%;
            width: 8px;
            height: 14px;
            background: #ff4500;
            animation-duration: 3.5s;
            animation-delay: 0s;
            filter: blur(1px);
        }

        .map-bg-fire-particles span:nth-child(2) {
            left: 18%;
            width: 6px;
            height: 10px;
            background: #ff6a00;
            animation-duration: 2.8s;
            animation-delay: 0.5s;
            filter: blur(1px);
        }

        .map-bg-fire-particles span:nth-child(3) {
            left: 32%;
            width: 10px;
            height: 18px;
            background: #ff3000;
            animation-duration: 4s;
            animation-delay: 1s;
            filter: blur(2px);
        }

        .map-bg-fire-particles span:nth-child(4) {
            left: 47%;
            width: 7px;
            height: 12px;
            background: #ff7700;
            animation-duration: 3.2s;
            animation-delay: 0.3s;
            filter: blur(1px);
        }

        .map-bg-fire-particles span:nth-child(5) {
            left: 60%;
            width: 5px;
            height: 9px;
            background: #ff4500;
            animation-duration: 2.5s;
            animation-delay: 1.5s;
            filter: blur(1px);
        }

        .map-bg-fire-particles span:nth-child(6) {
            left: 72%;
            width: 9px;
            height: 16px;
            background: #ff6a00;
            animation-duration: 3.8s;
            animation-delay: 0.8s;
            filter: blur(2px);
        }

        /* Continuous Magic Dust */
        .map-bg-magic-dust {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .map-bg-magic-dust span {
            position: absolute;
            width: 3px;
            height: 3px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 0 8px #fff, 0 0 15px #0ff;
            animation: magicFloat linear infinite;
            opacity: 0;
            bottom: -20px;
        }

        /* Randomize dust particles */
        .map-bg-magic-dust span:nth-child(1) {
            left: 10%;
            animation-duration: 15s;
            animation-delay: 0s;
            width: 4px;
            height: 4px;
            box-shadow: 0 0 10px #ffd200;
        }

        .map-bg-magic-dust span:nth-child(2) {
            left: 25%;
            animation-duration: 18s;
            animation-delay: 5s;
        }

        .map-bg-magic-dust span:nth-child(3) {
            left: 40%;
            animation-duration: 22s;
            animation-delay: 2s;
            width: 5px;
            height: 5px;
            box-shadow: 0 0 15px #43e97b;
        }

        .map-bg-magic-dust span:nth-child(4) {
            left: 55%;
            animation-duration: 16s;
            animation-delay: 8s;
        }

        .map-bg-magic-dust span:nth-child(5) {
            left: 70%;
            animation-duration: 20s;
            animation-delay: 3s;
            width: 3px;
            height: 3px;
            box-shadow: 0 0 8px #ff5f6d;
        }

        .map-bg-magic-dust span:nth-child(6) {
            left: 85%;
            animation-duration: 14s;
            animation-delay: 10s;
        }

        .map-bg-magic-dust span:nth-child(7) {
            left: 15%;
            animation-duration: 19s;
            animation-delay: 1s;
            width: 4px;
            height: 4px;
            box-shadow: 0 0 10px #ffd200;
        }

        .map-bg-magic-dust span:nth-child(8) {
            left: 35%;
            animation-duration: 17s;
            animation-delay: 6s;
        }

        .map-bg-magic-dust span:nth-child(9) {
            left: 65%;
            animation-duration: 21s;
            animation-delay: 4s;
            width: 5px;
            height: 5px;
            box-shadow: 0 0 15px #43e97b;
        }

        .map-bg-magic-dust span:nth-child(10) {
            left: 80%;
            animation-duration: 15s;
            animation-delay: 7s;
        }

        .map-bg-magic-dust span:nth-child(11) {
            left: 5%;
            animation-duration: 25s;
            animation-delay: 2s;
            box-shadow: 0 0 8px #ff5f6d;
        }

        .map-bg-magic-dust span:nth-child(12) {
            left: 95%;
            animation-duration: 23s;
            animation-delay: 9s;
        }

        .map-bg-magic-dust span:nth-child(13) {
            left: 50%;
            animation-duration: 16s;
            animation-delay: 12s;
            width: 4px;
            height: 4px;
            box-shadow: 0 0 10px #ffd200;
        }

        .map-bg-magic-dust span:nth-child(14) {
            left: 45%;
            animation-duration: 19s;
            animation-delay: 15s;
        }

        .map-bg-magic-dust span:nth-child(15) {
            left: 75%;
            animation-duration: 22s;
            animation-delay: 11s;
            width: 5px;
            height: 5px;
            box-shadow: 0 0 15px #43e97b;
        }

        @keyframes magicFloat {
            0% {
                transform: translateY(0vh) translateX(0) scale(0);
                opacity: 0;
            }

            15% {
                opacity: 0.8;
                transform: translateY(-15vh) translateX(20px) scale(1);
            }

            50% {
                opacity: 1;
                transform: translateY(-50vh) translateX(-20px) scale(1.2);
            }

            85% {
                opacity: 0.6;
                transform: translateY(-85vh) translateX(15px) scale(0.9);
            }

            100% {
                transform: translateY(-120vh) translateX(-10px) scale(0);
                opacity: 0;
            }
        }


        .map-bg-fire-particles span:nth-child(7) {
            left: 85%;
            width: 6px;
            height: 11px;
            background: #ff3000;
            animation-duration: 3s;
            animation-delay: 2s;
            filter: blur(1px);
        }

        .map-bg-fire-particles span:nth-child(8) {
            left: 92%;
            width: 8px;
            height: 14px;
            background: #ff7700;
            animation-duration: 2.7s;
            animation-delay: 0.2s;
            filter: blur(1px);
        }

        .map-bg-fire-particles span:nth-child(9) {
            left: 25%;
            width: 4px;
            height: 8px;
            background: #ffaa00;
            animation-duration: 2.2s;
            animation-delay: 1.8s;
            filter: blur(1px);
        }

        .map-bg-fire-particles span:nth-child(10) {
            left: 55%;
            width: 11px;
            height: 20px;
            background: #ff5500;
            animation-duration: 4.2s;
            animation-delay: 0.6s;
            filter: blur(2px);
        }

        @keyframes fireRise {
            0% {
                opacity: 0;
                transform: translateY(0) scale(1);
            }

            20% {
                opacity: 0.9;
                transform: translateY(-40px) scale(1.1);
            }

            80% {
                opacity: 0.4;
                transform: translateY(-180px) scale(0.6) rotate(10deg);
            }

            100% {
                opacity: 0;
                transform: translateY(-260px) scale(0.2);
            }
        }

        /* Ensure scroll container sits on top */
        .vmap-scroll {
            z-index: 2;
            position: relative;
        }

        #map-screen h2 {
            color: #fff;
            font-size: 2rem;
            text-shadow: var(--text-shadow);
            margin-bottom: 1.5rem;
        }

        /* ===================== NEW MAP LAYOUT (DOMAINS) ===================== */
        .domain-container {
            display: flex;
            gap: 2rem;
            width: 100%;
            max-width: 1440px;
            justify-content: center;
            align-items: stretch;
            margin-top: 1rem;
            padding: 0 1rem;
            flex-wrap: wrap;
        }

        .domain-column {
            flex: 1;
            min-width: 220px;
            background: rgba(20, 30, 48, 0.7);
            border: 2px solid rgba(79, 172, 254, 0.4);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .domain-header {
            text-align: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0.5rem;
        }

        .domain-header h4 {
            color: #4facfe;
            margin: 0;
            font-size: 1.1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .domain-header h3 {
            color: #fff;
            margin: 0.5rem 0 0 0;
            font-size: 1.3rem;
        }

        .sub-level-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .sub-level-item {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #fff;
        }

        .sub-level-item:hover:not(.locked) {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.2);
            border-color: #4facfe;
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
        }

        .sub-level-item.locked {
            filter: grayscale(1);
            opacity: 0.5;
            cursor: not-allowed;
        }

        .rank-badge-spot {
            width: 35px;
            height: 35px;
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px dotted rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 800;
            text-shadow: 1px 1px 2px #000;
        }

        .rank-badge-spot.rank-a {
            background: linear-gradient(135deg, #ffd200, #ff8c00);
            border: 2px solid #fff;
            color: #fff;
            box-shadow: 0 0 10px rgba(255, 210, 0, 0.8);
        }

        .rank-badge-spot.rank-p {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            border: 2px solid #fff;
            color: #fff;
        }

        .rank-badge-spot.rank-pp {
            background: linear-gradient(135deg, #ff5f6d, #ff9a9e);
            border: 2px solid #fff;
            color: #fff;
            font-size: 0.9rem;
            /* Slightly smaller for two letters */
        }

        .sub-level-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .rank-badge-spot {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px dashed rgba(255, 255, 255, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
        }

        .rank-badge-spot.completed {
            background: rgba(255, 210, 0, 0.2);
            border: 1px solid var(--primary-color);
        }

        .text-shadow {
            text-shadow: var(--text-shadow);
        }

        .hover-scale {
            transition: transform 0.2s;
        }

        .hover-scale:hover {
            transform: scale(1.05);
        }

        .animate-bounce {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-16px);
            }

            60% {
                transform: translateY(-8px);
            }
        }

        /* ===================== BATTLE SCREEN & ATMOSPHERE ===================== */
        #battle-screen {
            flex-direction: column;
            background: var(--dark-forest);
            /* Base fallback */
        }

        .btn-back {
            position: absolute;
            top: 20px;
            left: 30px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(10, 18, 25, 0.9);
            border: 2px solid rgba(255, 95, 109, 0.6);
            color: #fff;
            font-size: 1.2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 9999;
            /* Must be extremely high to beat the full-width HUD */
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            text-decoration: none;
            pointer-events: auto;
        }

        .btn-back:hover {
            background: rgba(255, 95, 109, 0.4);
            border-color: #ff5f6d;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(255, 95, 109, 0.8);
        }

        /* Atmospheric Layers */
        .bg-layer,
        .fog-layer,
        .dust-particles,
        .vignette-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .bg-layer {
            background: radial-gradient(circle at 50% 100%, #173633 0%, #061014 80%);
            opacity: 0.8;
        }

        .fog-layer {
            background: url('https://raw.githubusercontent.com/danielstuart14/CSS_FOG_ANIMATION/master/fog1.png') repeat-x;
            background-size: 200% auto;
            background-position: center bottom;
            opacity: 0.25;
            animation: fogMove 60s linear infinite;
            mix-blend-mode: screen;
        }

        @keyframes fogMove {
            0% {
                background-position: 0% bottom;
            }

            100% {
                background-position: 200% bottom;
            }
        }

        .vignette-overlay {
            box-shadow: inset 0 0 150px rgba(0, 0, 0, 0.9);
            z-index: 10;
        }

        /* Base RPG HUD Elements */
        .rpg-hud {
            position: relative;
            z-index: 20;
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5vw;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0) 100%);
            width: 100%;
            pointer-events: none;
            /* Let clicks pass through empty space */
        }

        .hud-panel,
        .hud-center {
            pointer-events: auto;
            /* Re-enable clicks for actual HUD elements */
        }

        .hud-panel {
            display: flex;
            align-items: center;
            background: var(--hud-bg);
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.6), inset 0 0 15px rgba(255, 255, 255, 0.05);
            border-radius: 50px;
            padding: 0.5rem;
            width: 32%;
            min-width: 250px;
        }

        .hud-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #2a2a35, #121218);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            border: 2px solid #555;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.8);
            flex-shrink: 0;
        }

        .hero-hud .hud-avatar {
            border-color: #ffd200;
            box-shadow: 0 0 15px rgba(255, 210, 0, 0.4);
        }

        .monster-hud .hud-avatar {
            border-color: #ff5f6d;
            box-shadow: 0 0 15px rgba(255, 95, 109, 0.4);
        }

        .hud-stats {
            flex: 1;
            margin: 0 1rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hud-name {
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.2rem;
            text-shadow: 1px 1px 2px #000;
        }

        .text-right {
            text-align: right;
        }

        .hp-track {
            width: 100%;
            height: 12px;
            background: #111;
            border-radius: 10px;
            border: 1px solid #333;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.8);
        }

        .hp-fill {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, #4facfe, #00f2fe);
            box-shadow: 0 0 10px #00f2fe;
            transition: width 0.3s ease-out, background-color 0.3s;
        }

        .monster-fill {
            background: linear-gradient(90deg, #ff5f6d, #ff9a9e);
            box-shadow: 0 0 10px #ff5f6d;
        }

        .right-align {
            display: flex;
            justify-content: flex-end;
        }

        .hp-text {
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
            text-shadow: 1px 1px 2px #000;
            min-width: 65px;
            text-align: center;
        }

        .hud-center {
            display: flex;
            gap: 1.5rem;
            text-align: center;
        }

        .neon-score,
        .neon-combo {
            font-weight: 800;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 2px;
        }

        .neon-score span {
            display: block;
            font-size: 1.8rem;
            color: #ffd200;
            text-shadow: 0 0 10px rgba(255, 210, 0, 0.6);
        }

        .neon-combo span {
            display: block;
            font-size: 1.8rem;
            color: #00f2fe;
            text-shadow: 0 0 10px rgba(0, 242, 254, 0.6);
        }

        /* --- Arena --- */
        .battle-arena {
            flex: 1;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 0 15vw;
            position: relative;
            min-height: 0;
            overflow: visible;
            width: 100%;
            margin: 0 auto;
            max-width: 1200px;
            z-index: 10;
        }

        .character-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 15;
            margin-bottom: 5vh;
        }

        /* Ground Platforms */
        .platform {
            width: 160px;
            height: 40px;
            background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 70%);
            position: absolute;
            bottom: -15px;
            z-index: -1;
        }

        .hero-platform {
            box-shadow: 0 0 30px rgba(255, 210, 0, 0.2);
            border-radius: 50%;
        }

        .monster-platform {
            box-shadow: 0 0 30px rgba(200, 40, 40, 0.2);
            border-radius: 50%;
        }

        /* Hero Sprite */
        #hero-sprite {
            height: 220px;
            object-fit: contain;
            filter: drop-shadow(0 15px 15px rgba(0, 0, 0, 0.6));
            transition: all 0.3s ease;
            animation: heroBreathe 3s ease-in-out infinite;
        }

        @keyframes heroBreathe {

            0%,
            100% {
                transform: scaleY(1);
            }

            50% {
                transform: scaleY(0.97) translateY(2px);
            }
        }

        /* Enemy Sprite */
        #monster-sprite {
            height: 380px;
            object-fit: contain;
            filter: drop-shadow(0 15px 15px rgba(0, 0, 0, 0.6)) drop-shadow(0 0 20px rgba(255, 0, 0, 0.2));
            transition: all 0.3s ease;
            animation: monsterFloat 4s ease-in-out infinite;
        }

        @keyframes monsterFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        #monster-name {
            color: #ff9a9e;
            text-shadow: 0 0 10px rgba(255, 0, 0, 0.8), 2px 2px 4px rgba(0, 0, 0, 1);
            font-size: 1.2rem;
            margin-top: 0.5rem;
            font-weight: 800;
            letter-spacing: 1px;
        }

        #hero-name {
            color: #ffeb3b;
            text-shadow: 0 0 10px rgba(255, 235, 59, 0.8), 2px 2px 4px rgba(0, 0, 0, 0.8);
            font-size: 1.3rem;
            font-weight: 800;
            margin-top: 0.5rem;
            letter-spacing: 1px;
            animation: floatIdle 3s ease-in-out infinite;
        }

        /* vs divider */
        .vs-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            align-self: center;
        }

        #feedback-message {
            font-size: clamp(1rem, 2.5vw, 1.6rem);
            font-weight: 800;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
            min-width: 120px;
            min-height: 40px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
            transform: scale(0.8);
        }

        #feedback-message.show {
            opacity: 1;
            transform: scale(1);
            animation: popFeedback 0.3s ease forwards;
        }

        @keyframes popFeedback {
            0% {
                transform: scale(0.6) translateY(10px);
                opacity: 0;
            }

            70% {
                transform: scale(1.15);
                opacity: 1;
            }

            100% {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .crit-hit {
            color: #fdf542;
        }

        .miss {
            color: #ff5f6d;
        }

        /* --- Question card --- */
        .question-card {
            flex: 0 0 auto;
            background: rgba(10, 18, 25, 0.9);
            backdrop-filter: blur(12px);
            border: 2px solid rgba(255, 210, 0, 0.3);
            border-radius: 15px;
            padding: 1.5rem 2rem;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(255, 210, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            width: 90%;
            max-width: 800px;
            margin: 0 auto 2rem auto;
            z-index: 20;
        }

        #question-text {
            font-size: clamp(1rem, 2.5vw, 1.35rem);
            font-weight: 700;
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
            text-align: center;
            margin-bottom: 1rem;
        }

        #options-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
        }

        .option-btn {
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(0.9rem, 1.8vw, 1.1rem);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            padding: 0.7rem 0.5rem;
            transition: all 0.2s;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            color: #e0e0e0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .option-btn:hover:not(:disabled) {
            background: rgba(255, 210, 0, 0.15);
            color: #ffd200;
            border-color: #ffd200;
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(255, 210, 0, 0.4);
        }

        .option-btn.correct {
            background: linear-gradient(135deg, #43e97b, #38f9d7) !important;
            color: #fff !important;
            border-color: transparent !important;
            animation: glowGreen 0.8s ease infinite alternate;
        }

        .option-btn.wrong {
            background: linear-gradient(135deg, #ff5f6d, #ff9a9e) !important;
            color: #fff !important;
            border-color: transparent !important;
        }

        @keyframes glowGreen {
            from {
                box-shadow: 0 0 10px rgba(67, 233, 123, 0.5);
            }

            to {
                box-shadow: 0 0 25px rgba(67, 233, 123, 0.9);
            }
        }

        .feedback-hint {
            grid-column: 1 / -1;
            font-size: 0.95rem;
            border-radius: 10px;
            padding: 0.6rem 0.8rem;
            background: rgba(79, 172, 254, 0.25);
            color: #ffffff;
            border: 1px solid rgba(79, 172, 254, 0.5);
            margin-top: 0.5rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
        }

        /* ===================== BATTLE BACKGROUNDS ===================== */
        /* Each level has a rich gradient fallback in case image fails to load */
        .bg-forest {
            background:
                linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)),
                url('https://images.unsplash.com/photo-1448375240586-dfd8d395ea6c?auto=format&fit=crop&q=80') center/cover,
                linear-gradient(160deg, #1a472a 0%, #2d6a4f 50%, #40916c 100%);
        }

        .bg-river {
            background:
                linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)),
                url('https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&q=80') center/cover,
                linear-gradient(160deg, #0077b6 0%, #0096c7 50%, #00b4d8 100%);
        }

        .bg-castle {
            background:
                linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://images.unsplash.com/photo-1599593256038-f996d9333904?auto=format&fit=crop&q=80') center/cover,
                linear-gradient(160deg, #2d1b69 0%, #4a2c8a 50%, #6a3fb5 100%);
        }

        .bg-volcano {
            background:
                linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://images.unsplash.com/photo-1462331940025-496dfbfc7564?auto=format&fit=crop&q=80') center/cover,
                linear-gradient(160deg, #7b0012 0%, #c9184a 50%, #ff6b35 100%);
        }

        .bg-dragon {
            background:
                linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('https://images.unsplash.com/photo-1519074069444-1ba4fff66d16?auto=format&fit=crop&q=80') center/cover,
                linear-gradient(160deg, #0d0d0d 0%, #1a0533 50%, #2d0066 100%);
        }


        /* ===================== ATTACK ANIMATIONS ===================== */

        /* Hero lunges RIGHT toward enemy */
        .hero-attack {
            animation: heroLunge 0.55s ease forwards;
        }

        @keyframes heroLunge {
            0% {
                transform: translateX(0) scale(1);
            }

            35% {
                transform: translateX(120px) scale(1.25) rotate(-8deg);
                filter: drop-shadow(0 0 20px rgba(255, 220, 50, 0.9));
            }

            65% {
                transform: translateX(90px) scale(1.15) rotate(-4deg);
            }

            100% {
                transform: translateX(0) scale(1);
            }
        }

        /* Enemy lunges LEFT toward hero */
        .enemy-attack {
            animation: enemyLunge 0.55s ease forwards;
        }

        @keyframes enemyLunge {
            0% {
                transform: translateX(0) scale(1);
            }

            35% {
                transform: translateX(-120px) scale(1.25) rotate(8deg);
                filter: drop-shadow(0 0 20px rgba(255, 60, 60, 0.9));
            }

            65% {
                transform: translateX(-90px) scale(1.15) rotate(4deg);
            }

            100% {
                transform: translateX(0) scale(1);
            }
        }

        /* Monster takes a hit */
        .monster-hit {
            animation: monsterHitFlash 0.5s ease forwards;
        }

        @keyframes monsterHitFlash {

            0%,
            100% {
                filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.5));
            }

            20% {
                filter: drop-shadow(0 0 0 0px red) brightness(3) sepia(1) hue-rotate(-20deg);
                transform: translate(6px, -4px) rotate(8deg);
            }

            40% {
                filter: drop-shadow(0 0 0 0px red) brightness(3) sepia(1) hue-rotate(-20deg);
                transform: translate(-6px, 4px) rotate(-8deg);
            }

            60% {
                filter: brightness(2);
                transform: translate(4px, -2px);
            }

            80% {
                filter: brightness(1.5);
                transform: translate(-2px, 2px);
            }
        }

        /* Hero takes a hit */
        .hero-hurt {
            animation: heroHurt 0.55s ease forwards;
        }

        @keyframes heroHurt {

            0%,
            100% {
                filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.5));
            }

            20% {
                filter: brightness(3) sepia(1) hue-rotate(300deg);
                transform: translate(-8px, 4px) rotate(-6deg);
            }

            40% {
                filter: brightness(2) sepia(1) hue-rotate(300deg);
                transform: translate(8px, -4px) rotate(6deg);
            }

            60% {
                filter: brightness(1.5);
                transform: translate(-4px, 2px);
            }

            80% {
                filter: brightness(1.2);
                transform: translate(2px, -2px);
            }
        }

        /* ===================== PROJECTILES ===================== */
        .projectile {
            position: absolute;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            z-index: 90;
            pointer-events: none;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
        }

        .projectile.bullet {
            background: radial-gradient(circle, #4facfe, #00f2fe);
            box-shadow: 0 0 20px #00f2fe;
        }

        .projectile.fireball {
            background: radial-gradient(circle, #ffd200, #ff5f6d);
            box-shadow: 0 0 20px #ff5f6d;
            width: 40px;
            height: 40px;
        }

        /* Red screen flash on player hit */
        .screen-flash-red {
            animation: flashRed 0.4s ease;
        }

        @keyframes flashRed {
            0% {
                box-shadow: inset 0 0 0 200px rgba(255, 0, 0, 0.4);
            }

            100% {
                box-shadow: inset 0 0 0 0 rgba(255, 0, 0, 0);
            }
        }

        /* Screen shake on wrong answer */
        .screen-shake {
            animation: screenShake 0.5s ease;
        }

        @keyframes screenShake {

            0%,
            100% {
                transform: translate(0, 0);
            }

            15% {
                transform: translate(-8px, 4px);
            }

            30% {
                transform: translate(8px, -4px);
            }

            45% {
                transform: translate(-6px, 6px);
            }

            60% {
                transform: translate(6px, -2px);
            }

            75% {
                transform: translate(-4px, 2px);
            }

            90% {
                transform: translate(4px, -2px);
            }
        }

        /* ===================== FLOATING DAMAGE NUMBERS ===================== */
        .damage-number {
            position: absolute;
            font-family: 'Fredoka', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            pointer-events: none;
            z-index: 100;
            white-space: nowrap;
            animation: floatUp 1.2s ease forwards;
            text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7);
        }

        .damage-number.positive {
            color: #43e97b;
            text-shadow: 0 0 12px rgba(67, 233, 123, 0.9), 2px 2px 6px rgba(0, 0, 0, 0.7);
        }

        .damage-number.negative {
            color: #ff5f6d;
            text-shadow: 0 0 12px rgba(255, 95, 109, 0.9), 2px 2px 6px rgba(0, 0, 0, 0.7);
        }

        @keyframes floatUp {
            0% {
                transform: translateY(0) scale(0.5);
                opacity: 1;
            }

            30% {
                transform: translateY(-30px) scale(1.4);
                opacity: 1;
            }

            70% {
                transform: translateY(-70px) scale(1.1);
                opacity: 0.8;
            }

            100% {
                transform: translateY(-100px) scale(0.8);
                opacity: 0;
            }
        }

        /* ===================== PARTICLES ===================== */
        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 99;
            animation: particleBurst 0.8s ease forwards;
        }

        @keyframes particleBurst {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }

            100% {
                transform: var(--tx, translate(80px, -80px)) scale(0);
                opacity: 0;
            }
        }

        /* ===================== MODAL ===================== */
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 2px solid #4facfe;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            max-width: 600px;
            width: 85%;
            box-shadow: 0 0 30px rgba(79, 172, 254, 0.6);
            color: #fff;
            animation: popFeedback 0.3s ease forwards;
        }

        .modal-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        .modal-content p {
            font-size: 1.25rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: #e0e0e0;
        }

        /* ===================== RESULT SCREEN ===================== */
        #result-screen {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 1.5rem;
        }

        #result-title {
            font-size: clamp(2rem, 5vw, 3rem);
            color: #fff;
            text-shadow: 0 0 30px rgba(79, 172, 254, 0.8);
            animation: bounce 2s infinite;
        }

        .result-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem 3rem;
            color: #fff;
            margin: 1.5rem 0;
            min-width: 280px;
        }

        .result-card h2,
        .result-card h3 {
            color: #fff;
            margin-bottom: 0.6rem;
        }

        .result-card .text-primary {
            color: #4facfe !important;
        }

        .result-card .text-info {
            color: #43e97b !important;
        }

        .result-card .text-warning {
            color: #ffd200 !important;
        }

        .result-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-result {
            font-family: 'Fredoka', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 2.5rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-result:hover {
            transform: scale(1.06) translateY(-3px);
        }

        .btn-next {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: #fff;
            box-shadow: 0 8px 20px rgba(67, 233, 123, 0.4);
        }

        .btn-restart {
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #333;
            box-shadow: 0 8px 20px rgba(247, 151, 30, 0.4);
        }

        .btn-map {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: #fff;
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.4);
        }

        /* ===== ADVENTURE MAP (HORIZONTAL) ===== */
        #map-screen {
            background: linear-gradient(to bottom, #1a2a6c, #112b3c, #0d1b2a);
            overflow: hidden;
            position: relative;
        }

        .adv-bg {
            position: absolute;
            inset: 0;
            background: url('https://raw.githubusercontent.com/danielstuart14/CSS_FOG_ANIMATION/master/fog1.png') repeat-x;
            background-size: cover;
            opacity: 0.15;
            animation: fogMove 80s linear infinite;
            pointer-events: none;
        }

        @keyframes fogMove {
            from {
                background-position: 0 bottom;
            }

            to {
                background-position: -200vw bottom;
            }
        }

        .hmap-header {
            position: absolute;
            top: 20px;
            left: 0;
            width: 100%;
            text-align: center;
            z-index: 10;
            pointer-events: none;
        }

        .hmap-header h2 {
            color: #ffd200;
            font-size: clamp(1.4rem, 3vw, 2.2rem);
            text-shadow: 0 0 15px rgba(255, 210, 0, 0.8), 2px 2px 4px rgba(0, 0, 0, 0.8);
            margin: 0;
            animation: glow-pulse 3s ease-in-out infinite;
        }

        .hmap-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.8rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin: 0.4rem 0 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
            background: rgba(0, 0, 0, 0.4);
            display: inline-block;
            padding: 4px 15px;
            border-radius: 15px;
        }

        .hmap-scroll {
            width: 100vw;
            height: 100vh;
            overflow-x: auto;
            overflow-y: hidden;
            cursor: grab;
            scrollbar-width: none;
            -ms-overflow-style: none;
            position: relative;
            scroll-behavior: smooth;
        }

        .hmap-scroll::-webkit-scrollbar {
            display: none;
        }

        .hmap-scroll:active,
        .hmap-scroll.dragging {
            cursor: grabbing;
            user-select: none;
        }

        /* The canvas draws the dotted path between nodes */
        /* ===== ADVENTURE MAP NODE WRAPPERS ===== */
        /* Monster images floating near each node */
        .map-monster {
            position: absolute;
            width: 160px;
            height: 160px;
            object-fit: contain;
            filter: drop-shadow(0 0 20px rgba(100, 50, 200, 0.8)) drop-shadow(0 0 40px rgba(0, 0, 0, 0.9));
            opacity: 0.85;
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
            z-index: 2;
            top: -40px;
            right: 90px;
            animation: monsterFloat 3s ease-in-out infinite;
        }

        .map-monster.flip {
            right: auto;
            left: 90px;
            transform: scaleX(-1);
        }

        .map-monster.flip:hover {
            transform: scaleX(-1) scale(1.08);
        }

        .map-monster:not(.flip):hover {
            transform: scale(1.08);
        }

        @keyframes monsterFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .map-monster.flip {
            animation: monsterFloatFlip 3s ease-in-out infinite;
        }

        @keyframes monsterFloatFlip {

            0%,
            100% {
                transform: scaleX(-1) translateY(0);
            }

            50% {
                transform: scaleX(-1) translateY(-10px);
            }
        }


        /* The container holding all nodes & banners */
        .hmap-world,
        .vmap-world {
            position: relative;
            width: 100%;
            min-height: 2600px;
            z-index: 2;
        }

        /* Node Wrappers (Absolutely positioned) */
        .hmap-nwrap {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            width: 130px;
            transform: translateX(-50%);
            z-index: 5;
        }

        /* MAP NODES themselves */
        .adv-node {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            position: relative;
            transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
            background: radial-gradient(circle at 35% 30%, #2e2e50, #0d0d20);
            border: 4px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.7), inset 0 2px 0 rgba(255, 255, 255, 0.12);
        }

        .adv-node::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: conic-gradient(rgba(255, 210, 0, 0.6) 0%, transparent 40%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: -1;
        }

        .adv-node:not(.locked):hover {
            transform: scale(1.15) translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 210, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            border-color: #ffd200;
        }

        .adv-node:not(.locked):hover::before {
            opacity: 0.8;
        }

        .adv-node.locked {
            background: radial-gradient(circle at 35% 30%, #141414, #080808);
            border-color: rgba(255, 255, 255, 0.06);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .adv-node.active {
            border-color: #43e97b;
            background: radial-gradient(circle at 35% 30%, #0d2e1a, #040f09);
            box-shadow: 0 0 25px rgba(67, 233, 123, 0.9), 0 0 60px rgba(67, 233, 123, 0.4), 0 8px 25px rgba(0, 0, 0, 0.6);
            animation: nodeGlow 1.6s ease-in-out infinite;
            cursor: pointer;
            opacity: 1;
        }

        .adv-node.completed {
            background: radial-gradient(circle at 35% 30%, #2e1e00, #120c00);
            border-color: #ffd200;
            box-shadow: 0 0 18px rgba(255, 210, 0, 0.6), 0 8px 25px rgba(0, 0, 0, 0.6);
            cursor: pointer;
            opacity: 1;
        }

        .adv-nn {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
            z-index: 2;
        }

        .adv-node.locked .adv-nn {
            display: none;
        }

        .adv-nr {
            font-size: 1.6rem;
            font-weight: 900;
            color: #ffd200;
            text-shadow: 0 0 14px rgba(255, 210, 0, 1);
            display: none;
            z-index: 2;
        }

        .adv-node.completed .adv-nr {
            display: block;
        }

        .adv-node.completed {
            overflow: visible !important;
        }

        .adv-nr.rank-a {
            color: #ffd200;
        }

        .adv-nr.rank-p {
            color: #4facfe;
        }

        .adv-nr.rank-pp {
            color: #ff9a9e;
        }

        .adv-rank-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 34px;
            height: 34px;
            background: rgba(0, 0, 0, 0.85);
            border: 2px solid #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.8);
        }

        .adv-rank-badge.rank-a {
            color: #43e97b;
            border-color: #43e97b;
            text-shadow: 0 0 5px rgba(67, 233, 123, 0.5);
        }

        .adv-rank-badge.rank-p {
            color: #ffd200;
            border-color: #ffd200;
            text-shadow: 0 0 5px rgba(255, 210, 0, 0.5);
        }

        .adv-rank-badge.rank-pp {
            color: #ff5f6d;
            border-color: #ff5f6d;
            text-shadow: 0 0 5px rgba(255, 95, 109, 0.5);
        }

        .adv-nlock {
            font-size: 1.6rem;
            display: none;
            z-index: 2;
        }

        .adv-node.locked .adv-nlock {
            display: block;
        }

        .adv-nl {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.8rem;
            text-align: center;
            line-height: 1.3;
            font-weight: 700;
            text-shadow: 0 1px 4px #000;
            width: 140px;
        }

        .adv-node.locked~.adv-nl {
            color: rgba(255, 255, 255, 0.4);
        }

        .hmap-legend {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 12px 20px;
            border-radius: 12px;
            color: #ccc;
            font-size: 0.8rem;
            display: flex;
            gap: 15px;
            align-items: center;
            z-index: 10;
            backdrop-filter: blur(4px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        .hmap-legend strong {
            color: #fff;
        }

        .leg-a {
            color: #43e97b;
            font-weight: bold;
            font-size: 1rem;
        }

        .leg-p {
            color: #ffd200;
            font-weight: bold;
            font-size: 1rem;
        }

        .leg-pp {
            color: #ff5f6d;
            font-weight: bold;
            font-size: 1rem;
        }

        /* ===================== FINAL SCORE SCREEN ===================== */
        #final-score-screen {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            justify-content: flex-start;
            align-items: center;
            overflow-y: auto;
            padding: 2rem;
            color: #fff;
        }

        #final-score-screen h2 {
            font-size: 3rem;
            color: #ffd200;
            text-shadow: 0 0 20px rgba(255, 210, 0, 0.6);
            margin-bottom: 2rem;
            text-transform: uppercase;
            text-align: center;
        }

        .scorecard-container {
            width: 100%;
            max-width: 800px;
            background: rgba(10, 15, 25, 0.85);
            border-radius: 20px;
            border: 2px solid rgba(67, 233, 123, 0.4);
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .score-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border-left: 4px solid transparent;
            transition: transform 0.2s;
        }
        .score-row:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.1);
        }

        .score-level-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            flex: 1;
        }

        .score-standard {
            font-size: 0.9rem;
            color: #ccc;
            margin-right: 2rem;
            background: rgba(0,0,0,0.4);
            padding: 4px 10px;
            border-radius: 6px;
        }

        .score-rank {
            font-weight: bold;
            font-size: 1.2rem;
            min-width: 250px;
            text-align: right;
        }

        .score-rank.rank-a { color: #43e97b; }
        .score-rank.rank-p { color: #ffd200; }
        .score-rank.rank-pp { color: #ff5f6d; }
    </style>

    <script>
        const level1_1 = {
            name: "1.1 Expressing Ratios",
            background: "bg-forest",
            monsterName: "Moss Golem",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster1.png",
            monsterHP: 100,
            standard: "6.RP.A.1",
            questions: [
                {
                    question: "A school has an enrollment of 600 students. 330 of the students are girls. Express the fraction of students who are boys in lowest terms.",
                    options: ["12/20", "11/20", "9/20", "13/20"],
                    correct: 2,
                    explanation: "First, subtract girls from total to get boys: 600 - 330 = 270. Initial ratio is 270/600. Divide by the GCF (30) to get 9/20."
                },
                {
                    question: "The ratio of boys to girls in Mr. Yang's class is 4:3. If there are 16 boys in the class, how many girls are there?",
                    options: ["8 girls", "12 girls", "15 girls", "16 girls"],
                    correct: 1,
                    explanation: "Create a proportion 4/3 = 16/x. 4 * 4 = 16 (boys), so multiply girls (3) by 4 to get 12 girls."
                },
                {
                    question: "In the 14th century, the Sultan of Brunei noticed that his ratio of emeralds to rubies was the same as the ratio of diamonds to pearls. If he had 85 emeralds, 119 rubies, and 45 diamonds, how many pearls did he have?",
                    options: ["17", "22", "58", "63"],
                    correct: 3,
                    explanation: "Ratio of emeralds to rubies is 85/119. 85/119 = 45/x. Cross-multiply: 85x = 5355. x = 63."
                },
                {
                    question: "Mr. Fullingham has 75 geese and 125 turkeys. What is the lowest ratio of geese to turkeys?",
                    options: ["75:125", "15:25", "5:8", "3:5"],
                    correct: 3,
                    explanation: "Divide both 75 and 125 by their GCF (25) to get 3:5."
                },
                {
                    question: "Mr. Fullingham has 75 geese and 125 turkeys. What is the lowest ratio of the number of geese to the total number of birds?",
                    options: ["75:200", "3:8", "125:200", "5:8"],
                    correct: 1,
                    explanation: "Total birds = 75 + 125 = 200. Ratio of geese to total birds is 75:200. Divide by GCF (25) to get 3:8."
                },
                {
                    question: "Mr. Fullingham has 75 geese and 125 turkeys. Mr. Pasquale has the same ratio of geese to turkeys. Mr. Pasquale has 51 geese. How many turkeys does he have?",
                    options: ["85", "136", "34", "68"],
                    correct: 0,
                    explanation: "Lowest ratio is 3/5. Proportion: 3/5 = 51/x. Cross multiply: 3x = 255. Divide by 3 to get x = 85."
                },
                {
                    question: "Fluffy and Mimsy are competing to catch mice. Fluffy caught 66 mice. Mimsy caught 44 mice. What is the lowest ratio of mice Fluffy caught to mice Mimsy caught?",
                    options: ["6:4", "4:6", "3:2", "2:3"],
                    correct: 2,
                    explanation: "66:44 can be reduced by dividing by the GCF of 22 to get 3:2."
                },
                {
                    question: "Jenny Jumping Frog laid 345 eggs in the spring. 23 of those eggs lived to become adult frogs. What is the lowest ratio of eggs to adult frogs?",
                    options: ["345:23", "69:23", "15:1", "60:4"],
                    correct: 2,
                    explanation: "Divide both sides of 345:23 by the GCF of 23 to get 15:1."
                },
                {
                    question: "Imelda tried on 250 pairs of shoes and bought 20 pairs. What is the lowest ratio of the number of pairs she tried on to the number she bought?",
                    options: ["2:25", "20:250", "25:2", "50:4"],
                    correct: 2,
                    explanation: "Divide both sides of 250:20 by the GCF of 10 to get 25:2."
                },
                {
                    question: "The Hawks little league team has 7 brunettes, 5 blonds, and 2 redheads. What is the lowest ratio of redheads to the entire team?",
                    options: ["2:7", "2:5", "2:12", "1:7"],
                    correct: 3,
                    explanation: "Total players = 7+5+2 = 14. Ratio = 2:14. Divide by 2 to get 1:7."
                }
            ]
        };


        const level1_2 = {
            name: "1.2 Unit Rates",
            background: "bg-river",
            monsterName: "Water Serpent",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster2.png",
            monsterHP: 100,
            standard: "6.RP.A.2",
            questions: [
                {
                    question: "Cantaloupes are on sale for 2 for $1.25. What is the unit rate?",
                    options: ["64.5¢/cantaloupe", "63.5¢/cantaloupe", "62.5¢/cantaloupe", "61.5¢/cantaloupe"],
                    correct: 2,
                    explanation: "Set up the proportion 2/1.25 = 1/x. Dividing 1.25 by 2 gives $0.625 or 62.5¢."
                },
                {
                    question: "Which is a better price: 5 for $1.00, 4 for 85¢, 2 for 25¢, or 6 for $1.10?",
                    options: ["5 for $1.00", "4 for 85¢", "2 for 25¢", "6 for $1.10"],
                    correct: 2,
                    explanation: "2 for 25¢ gives a unit price of 12.5¢ ($0.125), which is the lowest price per unit."
                },
                {
                    question: "A box of 3 picture hangers sells for $2.22. What is the unit rate?",
                    options: ["$1.11 per hanger", "$2.22 per hanger", "74¢ per hanger", "98¢ per hanger"],
                    correct: 2,
                    explanation: "To find the price of each hanger, divide $2.22 by 3. $2.22 / 3 = $0.74 or 74¢."
                },
                {
                    question: "Store A: 5 cans for $3.45. Store B: 7 cans for $5.15. Store C: 4 cans for $2.46. Store D: 6 cans for $4.00. Better price?",
                    options: ["Store A", "Store B", "Store C", "Store D"],
                    correct: 2,
                    explanation: "Store C has the lowest unit rate: $2.46 ÷ 4 = $0.615 per can."
                },
                {
                    question: "Store A: 5 cans for $3.45. Store C: 4 cans for $2.46. How much money would you save buying 20 cans from C instead of A?",
                    options: ["$1.75", "$1.25", "$1.50", "95¢"],
                    correct: 2,
                    explanation: "Store A unit rate: $0.69 (*20 = $13.80). Store C unit rate: $0.615 (*20 = $12.30). Difference: $13.80 - $12.30 = $1.50."
                },
                {
                    question: "Beverly drove 284 miles at a constant speed of 58 mph. How long did it take?",
                    options: ["4 hours and 45 minutes", "4 hours and 54 minutes", "4 hours and 8 minutes", "4 hours and 89 minutes"],
                    correct: 1,
                    explanation: "Distance ÷ rate = time. 284 ÷ 58 ≈ 4.9 hours. 0.9 hours x 60 mins = 54 mins. So, 4 hours 54 minutes."
                },
                {
                    question: "Don earns $7.55/hr at job 1 (10 hours) and $8.45/hr at job 2 (15 hours). What were his average earnings per hour?",
                    options: ["$8.00", "$8.09", "$8.15", "$8.13"],
                    correct: 1,
                    explanation: "Total earned: ($7.55*10) + ($8.45*15) = $75.50 + $126.75 = $202.25. Total hours: 25. Avg: $202.25/25 = $8.09/hr."
                },
                {
                    question: "Paper towels cost $10.48 for a package of 8 rolls. What is the unit price?",
                    options: ["$1.31 per roll", "$1.05 per roll", "$1.52 per roll", "$1.27 per roll"],
                    correct: 0,
                    explanation: "Divide the total cost by the number of rolls: $10.48 ÷ 8 = $1.31 per roll."
                },
                {
                    question: "It took Marjorie 15 mins to drive to a school 4 miles away. What was her speed in mph?",
                    options: ["16 mph", "8 mph", "4 mph", "30 mph"],
                    correct: 0,
                    explanation: "15 mins is 1/4 of an hour. 4 miles in 1/4 hour = 16 miles in 1 full hour (16 mph)."
                },
                {
                    question: "Louise walks 2 miles in 30 minutes. At what rate is she walking?",
                    options: ["2 mph", "3 mph", "4 mph", "5 mph"],
                    correct: 2,
                    explanation: "She walks 2 miles in 30 minutes (half an hour). In one full hour (60 minutes), she would walk 4 miles (4 mph)."
                }
            ]
        };


        const level1_3 = {
            name: "1.3 Finding Percent",
            background: "bg-castle", // can keep the castle background for variety
            monsterName: "Percent Phantom",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster3.png",
            monsterHP: 100,
            standard: "6.RP.A.3.C",
            questions: [
                {
                    question: "What is 25% of 24?",
                    options: ["5", "6", "11", "17"],
                    correct: 1,
                    explanation: "25/100 * 24 = 6. Alternatively, 25% is 1/4, and 24 / 4 = 6."
                },
                {
                    question: "What is 15% of 60?",
                    options: ["9", "12", "15", "25"],
                    correct: 0,
                    explanation: "15/100 * 60 = 9."
                },
                {
                    question: "The number 9 is what percent of 72?",
                    options: ["7.2%", "8%", "12.5%", "14%"],
                    correct: 2,
                    explanation: "9 / 72 = x / 100. 9 * 100 = 72x. 900 / 72 = 12.5%."
                },
                {
                    question: "How much is 30% of 190?",
                    options: ["45", "57", "60", "63"],
                    correct: 1,
                    explanation: "30/100 * 190 = 57."
                },
                {
                    question: "Daniel has 280 cards. 15% are highly collectable. How many are collectable?",
                    options: ["15 cards", "19 cards", "42 cards", "47 cards"],
                    correct: 2,
                    explanation: "15/100 * 280 = x. 100x = 4200. x = 42 cards."
                },
                {
                    question: "A team consumed 80% of the water, which was 8 gallons. How much was provided in total?",
                    options: ["10 gallons", "12 gallons", "15 gallons", "18.75 gallons"],
                    correct: 0,
                    explanation: "8 / x = 80 / 100. 800 = 80x. x = 10 gallons."
                },
                {
                    question: "Joshua brought 156 of his 678 Legos. What percentage did he bring?",
                    options: ["4%", "23%", "30%", "43%"],
                    correct: 1,
                    explanation: "156 / 678 = x / 100. 15600 = 678x. x ≈ 23%."
                },
                {
                    question: "Alexis hit 8 balls out of 15 into the outfield. Which equation finds the percentage?",
                    options: ["15 / 8 = x / 100", "15 / 100 = x / 8", "8x = (100)(15)", "8 / 15 = x / 100"],
                    correct: 3,
                    explanation: "The part (8) over the whole (15) equals the percentage (x) over 100."
                },
                {
                    question: "Of Nikki's 78 flowers, 32% are roses. Approximately how many roses does she have?",
                    options: ["18 roses", "25 roses", "28 roses", "41 roses"],
                    correct: 1,
                    explanation: "32/100 * 78 = 0.32 * 78 = 24.96 ≈ 25 roses."
                },
                {
                    question: "Victor took out 30% of paper. Paul used 6, Allison 8, Victor 10. How many sheets were NOT taken out?",
                    options: ["24 sheets", "50 sheets", "56 sheets", "80 sheets"],
                    correct: 2,
                    explanation: "Used = 24 (30%). 24 / x = 30 / 100 -> x = 80 (total). Not taken out = 80 - 24 = 56 sheets."
                }
            ]
        };

        const level2_1 = {
            name: "Division of Fractions",
            background: "bg-forest",
            monsterName: "Fraction Phantom",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster4.png", // Will be swapped out if provided
            monsterHP: 100,
            standard: "6.NS.A.1",
            questions: [
                {
                    question: "What is the quotient of 20 divided by one-fourth?",
                    options: ["80", "24", "5", "15"],
                    correct: 0,
                    explanation: "20 ÷ 1/4 = 20 × 4/1 = 80"
                },
                {
                    question: "Do the following operation: 3/4 ÷ 7/8 =",
                    options: ["21/32", "1/2", "3/7", "6/7"],
                    correct: 3,
                    explanation: "3/4 ÷ 7/8 = 3/4 × 8/7 = 24/28 = 6/7"
                },
                {
                    question: "Do the following operation: 2/5 ÷ 8/15 =",
                    options: ["16/75", "3/4", "4/3", "2/3"],
                    correct: 1,
                    explanation: "2/5 ÷ 8/15 = 2/5 × 15/8 = 30/40 = 3/4"
                },
                {
                    question: "Do the following operation: 1 1/2 ÷ 3/4 =",
                    options: ["4", "1/2", "3/4", "2"],
                    correct: 3,
                    explanation: "1 1/2 = 3/2. 3/2 ÷ 3/4 = 3/2 × 4/3 = 12/6 = 2"
                },
                {
                    question: "Solve: 2/3 ÷ 2 5/6 =",
                    options: ["4/17", "4 1/4", "8/17", "2 1/17"],
                    correct: 0,
                    explanation: "2 5/6 = 17/6. 2/3 ÷ 17/6 = 2/3 × 6/17 = 12/51 = 4/17"
                },
                {
                    question: "Do the following operation: 5/9 ÷ 10/21 =",
                    options: ["6/7", "1 1/6", "4/7", "7/9"],
                    correct: 1,
                    explanation: "5/9 ÷ 10/21 = 5/9 × 21/10 = 105/90 = 1 1/6"
                },
                {
                    question: "Do the following operation: 5/8 ÷ 3/4 =",
                    options: ["1 1/6", "1 1/5", "5/6", "2/3"],
                    correct: 2,
                    explanation: "5/8 ÷ 3/4 = 5/8 × 4/3 = 20/24 = 5/6"
                },
                {
                    question: "Do the following operation: 3 2/3 ÷ 2 1/6 =",
                    options: ["8/13", "1 2/13", "1 5/13", "1 9/13"],
                    correct: 3,
                    explanation: "11/3 ÷ 13/6 = 11/3 × 6/13 = 66/39 = 22/13 = 1 9/13"
                },
                {
                    question: "Do the following operation: 9/10 ÷ 5/6 =",
                    options: ["1 2/25", "3 3/4", "7 5/8", "1 1/5"],
                    correct: 0,
                    explanation: "9/10 ÷ 5/6 = 9/10 × 6/5 = 54/50 = 27/25 = 1 2/25"
                },
                {
                    question: "Evaluate: 5/6 ÷ 11/12 =",
                    options: ["1/2", "10/11", "1/4", "4"],
                    correct: 1,
                    explanation: "5/6 ÷ 11/12 = 5/6 × 12/11 = 60/66 = 10/11"
                }
            ]
        };

        const level2_2 = {
            name: "2.2 Division of Whole Numbers",
            background: "bg-river",
            monsterName: "Division Destroyer",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster5.png",
            monsterHP: 100,
            standard: "6.NS.B.2",
            questions: [
                {
                    question: "A team of 12 players got an award of $1,800 for winning a championship football game. If the captain of the team is allowed to keep $315, how much money would each of the other players get? (Assume they split it equally.)",
                    options: ["$135", "$125", "$150", "$123.75"],
                    correct: 0,
                    explanation: "First, find the difference of $1,800 - $315 = $1,485. Then, divide $1,485 ÷ 11 = $135."
                },
                {
                    question: "Find the quotient and remainder. 933 ÷ 12 = ?",
                    options: ["77 R 9", "77 R 7", "67 R 7", "67 R 9"],
                    correct: 0,
                    explanation: "Use the standard long division algorithm. 933 ÷ 12 = 77 R 9."
                },
                {
                    question: "What is the quotient when 2,125 is divided by 85?",
                    options: ["35", "45", "23", "25"],
                    correct: 3,
                    explanation: "Use the standard long division algorithm. 2,125 ÷ 85 = 25."
                },
                {
                    question: "Nick worked 22 hours one week and 36 hours the following week. He earned a total of $1,595. How much did he earn per hour during the two-week period?",
                    options: ["$27.50", "$27.00", "$16.00", "$12.50"],
                    correct: 0,
                    explanation: "Add to find the total hours worked: 22 + 36 = 58. Then divide $1,595 ÷ 58 = $27.50 per hour."
                },
                {
                    question: "Peter gets a salary of $125 per week. He wants to buy a new television that costs $3,960. If he saves $55 per week, which expression could he use to figure out how many weeks it will take him to save enough?",
                    options: ["$3,960 ÷ ($125 - $55)", "$3,960 - ($125)($55)", "($3,960 ÷ $125) ÷ $55", "$3,960 ÷ $55"],
                    correct: 3,
                    explanation: "To find the answer, divide $3,960 ÷ 55. The $125 is unnecessary information."
                },
                {
                    question: "21 hunters formed a hunting party. They split 1,575 pounds of meat evenly among themselves. How much meat did each person carry home?",
                    options: ["65 pounds", "175 pounds", "75 pounds", "85 pounds"],
                    correct: 2,
                    explanation: "1,575 ÷ 21 = 75 pounds each."
                },
                {
                    question: "In the variety package of jelly beans, there are 11 varieties with an equal number of each type. Each package contains 143 jelly beans. How many jelly beans of each type are in the package?",
                    options: ["7", "9", "11", "13"],
                    correct: 3,
                    explanation: "Divide the total number of jelly beans by the number of varieties. 143 ÷ 11 = 13."
                },
                {
                    question: "There are 49 cats and each has the same size litter of kittens. Together, they have 343 kittens. How many kittens are in each litter?",
                    options: ["5", "7", "9", "11"],
                    correct: 1,
                    explanation: "Divide the number of kittens by the number of cats. 343 ÷ 49 = 7."
                },
                {
                    question: "Judy traveled from Atlanta, Georgia to Silver Spring, MD which is a distance of 636 miles. If it took Judy 12 hours to travel that distance, what was her average speed?",
                    options: ["53 mph", "63 mph", "58 mph", "59 mph"],
                    correct: 0,
                    explanation: "Divide the number of miles by the hours it took to drive. 636 ÷ 12 = 53 mph."
                },
                {
                    question: "An expert typist typed 9,000 words in two hours. What is her typing rate in words per minute?",
                    options: ["45 words per minute", "60 words per minute", "75 words per minute", "90 words per minute"],
                    correct: 2,
                    explanation: "1 hour = 60 minutes, so 2 hours = 120 minutes. 9,000 ÷ 120 = 75 words per minute."
                }
            ]
        };

        const level2_3 = {
            name: "2.3 Operations with Decimals",
            background: "bg-castle",
            monsterName: "Decimal Demon",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster6.png",
            monsterHP: 100,
            standard: "6.NS.B.3",
            questions: [
                {
                    question: "Three friends went out to lunch. Ben's meal cost $7.25, Frank's cost $8.16, and Herman's cost $5.44. If they split the check evenly, how much did each pay?",
                    options: ["$6.95", "$7.75", "$7.15", "$6.55"],
                    correct: 0,
                    explanation: "$7.25 + $8.16 + $5.44 = $20.85. Then $20.85 ÷ 3 = $6.95."
                },
                {
                    question: "Which of these is the standard form of twenty and sixty-three thousandths?",
                    options: ["20.63000", "20.0063", "20.63", "20.063"],
                    correct: 3,
                    explanation: "The whole number part is 20 and sixty-three thousandths is written .063, giving 20.063."
                },
                {
                    question: "Mr. Zito bought a bicycle for $160 and spent $12.50 on repairs. If he sold it for $215, what was his profit?",
                    options: ["$147.50", "$42.50", "$67.50", "$55.00"],
                    correct: 1,
                    explanation: "$160 + $12.50 = $172.50 (total investment). $215.00 − $172.50 = $42.50 profit."
                },
                {
                    question: "Alec bought a glove for $15.99, a cup for $5.99, and a baseball for $4.29. He paid with two $20 bills. How much change did he receive?",
                    options: ["$13.73", "$14.73", "$14.83", "$13.83"],
                    correct: 0,
                    explanation: "$15.99 + $5.99 + $4.29 = $26.27. $40.00 − $26.27 = $13.73."
                },
                {
                    question: "At $3.69 per gallon, how much would 15 gallons of gasoline cost?",
                    options: ["$53.25", "$55.35", "$51.75", "None of these"],
                    correct: 1,
                    explanation: "$3.69 × 15 = $55.35."
                },
                {
                    question: "Peter's odometer read 18,368 miles at the start and 19,432 miles at the end of a trip. Fuel cost 15 cents per mile. How much was spent on fuel?",
                    options: ["$175.60", "$158.60", "$159.60", "$175.00"],
                    correct: 2,
                    explanation: "19,432 − 18,368 = 1,064 miles. 1,064 × $0.15 = $159.60."
                },
                {
                    question: "Evaluate: 6.675 + 2.45 + 0.055",
                    options: ["9.675", "8.775", "8.18", "9.180"],
                    correct: 3,
                    explanation: "Line up the decimal points and add: 6.675 + 2.45 + 0.055 = 9.180."
                },
                {
                    question: "A paperback book costs $4.75 and the hardcover costs $11.50. How much would be saved buying paperbacks for 20 students instead of hardcovers?",
                    options: ["$155.00", "$135.00", "$115.00", "$145.00"],
                    correct: 1,
                    explanation: "$11.50 × 20 = $230. $4.75 × 20 = $95. $230 − $95 = $135.00 saved."
                },
                {
                    question: "Cerise bought jeans for $13.25, a blouse for $12.99, and shoes for $45.00. How much change from $100.00?",
                    options: ["$39.86", "$29.86", "$28.76", "$39.76"],
                    correct: 2,
                    explanation: "$13.25 + $12.99 + $45.00 = $71.24. $100.00 − $71.24 = $28.76."
                },
                {
                    question: "Which of these numbers has a 3 in the ten-thousandths place?",
                    options: ["17.44356", "93.53535", "23.03325", "90.00003"],
                    correct: 1,
                    explanation: "The ten-thousandths place is four digits to the right of the decimal. In 93.53535, the 4th decimal digit is 3."
                }
            ]
        };

        const level3_1 = {
            name: "3.1 Area of Trapezoids",
            background: "bg-forest",
            monsterName: "Stone Titan",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster7.png",
            monsterHP: 100,
            standard: "6.G.A.1",
            questions: [
                {
                    question: "Find the area of a trapezoid with parallel sides of 8.2 in. and 11.8 in. and a height of 6 in.",
                    options: ["70 sq. in.", "30 sq. in.", "60 sq. in.", "120 sq. in."],
                    correct: 2,
                    explanation: "A = ½(a+b)h = ½(8.2+11.8)×6 = ½×20×6 = 60 sq. in."
                },
                {
                    question: "The area of a trapezoid is 100 sq. ft. Its parallel sides measure 6 ft. and 10 ft. Find the height.",
                    options: ["10 ft.", "12 ft.", "12.5 ft.", "15 ft."],
                    correct: 2,
                    explanation: "100 = ½(6+10)h = 8h. So h = 100÷8 = 12.5 ft."
                },
                {
                    question: "The area of a trapezoid is 50 sq. cm. One base is 8.5 cm and the height is 4 cm. Find the length of the other base.",
                    options: ["8 cm.", "15.5 cm.", "17.5 cm.", "16.5 cm."],
                    correct: 3,
                    explanation: "50 = ½(a+8.5)×4 → a+8.5 = 25. So a = 25−8.5 = 16.5 cm."
                },
                {
                    question: "A trapezoid has area 90 sq. cm. One base is 3 more than twice the other. Height is 8 cm. Find both bases.",
                    options: ["6.5 cm. and 16 cm.", "4 cm. and 18.5 cm.", "5 cm. and 13 cm.", "6 cm. and 15 cm."],
                    correct: 0,
                    explanation: "Let bases be a and (3+2a). ½(a+3+2a)×8=90 → 3a+3=22.5 → a=6.5 cm, b=16 cm."
                },
                {
                    question: "In trapezoid ABCD, AB=6cm, DC=10cm, height=7cm. John says area=51 sq.cm; Jose uses the formula and gets 112 sq.cm. Who is correct?",
                    options: ["John", "Jose", "Both are incorrect. Area is 56 sq. cm.", "Both are correct"],
                    correct: 2,
                    explanation: "A = ½(6+10)×7 = 56 sq. cm. John miscalculated one triangle, and Jose also got the wrong answer."
                },
                {
                    question: "Using A = ½(a+b)h, find the area when a = 9.8 cm, b = 6.9 cm, h = 8.8 cm.",
                    options: ["61.48 sq. cm.", "66.48 sq. cm.", "73.48 sq. cm.", "76.48 sq. cm."],
                    correct: 2,
                    explanation: "A = ½(9.8+6.9)×8.8 = ½×16.7×8.8 = 73.48 sq. cm."
                },
                {
                    question: "Isosceles trapezoid ABCD: AD=BC=5 cm, AB=8 cm, DC=14 cm. Find the area. (Hint: use the Pythagorean theorem to find height.)",
                    options: ["88 sq. cm.", "44 sq. cm.", "56 sq. cm.", "64 sq. cm."],
                    correct: 1,
                    explanation: "DE=(14−8)/2=3. h²=5²−3²=16, h=4. Area=½(8+14)×4=44 sq. cm."
                },
                {
                    question: "Parallelogram ABCD has area 120 sq. cm with DC=10 cm. Point E is on AB with AE=6 cm. Find the area of trapezoid AECD.",
                    options: ["72 sq. cm.", "84 sq. cm.", "96 sq. cm.", "108 sq. cm."],
                    correct: 2,
                    explanation: "Height h=120÷10=12 cm. Area of trapezoid AECD=½(6+10)×12=96 sq. cm."
                },
                {
                    question: "Which formula correctly calculates the area of a trapezoid with parallel sides a and b, and perpendicular height h?",
                    options: ["A = (a+b)×h", "A = ½×a×b×h", "A = ½(a+b)×h", "A = 2(a+b)×h"],
                    correct: 2,
                    explanation: "The trapezoid area formula is A = ½(a+b)h, where a and b are the parallel sides and h is the perpendicular height."
                }
            ]
        };

        const level3_2 = {
            name: "3.2 Area",
            background: "bg-river",
            monsterName: "River Golem",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster8.png",
            monsterHP: 100,
            standard: "6.G.A.1",
            questions: [
                {
                    question: "What is the area of a rectangle with length 9 units and width 7 units?",
                    options: ["16 square units", "63 square units", "32 square units", "45 square units"],
                    correct: 1,
                    explanation: "Area of a rectangle = length × width. A = 9 × 7 = 63 square units."
                },
                {
                    question: "What is the area of a square with a side length of 6 units?",
                    options: ["12 square units", "24 square units", "18 square units", "36 square units"],
                    correct: 3,
                    explanation: "Area of a square = (side)². A = 6² = 36 square units."
                },
                {
                    question: "What is the area of a right triangle with base 3 units and height 4 units?",
                    options: ["12 square units", "6 square units", "20 square units", "10 square units"],
                    correct: 1,
                    explanation: "Area of a triangle = ½ × base × height. A = ½ × 3 × 4 = 6 square units."
                },
                {
                    question: "What is the area of a rhombus with base 4 units and vertical height 3 units?",
                    options: ["16 square units", "12 square units", "8 square units", "20 square units"],
                    correct: 1,
                    explanation: "A rhombus is a parallelogram. Area = base × height = 4 × 3 = 12 square units."
                },
                {
                    question: "What is the area of a parallelogram with base 7 units and vertical height 3 units?",
                    options: ["28 square units", "12 square units", "14 square units", "21 square units"],
                    correct: 3,
                    explanation: "Area of a parallelogram = base × height = 7 × 3 = 21 square units."
                },
                {
                    question: "A small square (side 4) is inside a larger square (side 8). What is the area of the shaded region (between them)?",
                    options: ["64 square units", "48 square units", "16 square units", "80 square units"],
                    correct: 1,
                    explanation: "Larger square area = 8² = 64. Smaller square area = 4² = 16. Shaded area = 64 − 16 = 48 square units."
                },
                {
                    question: "What is the area of a triangle with base 5 units and vertical height 2.8 units?",
                    options: ["15 square units", "7 square units", "14 square units", "40 square units"],
                    correct: 1,
                    explanation: "A = ½ × base × height = ½ × 5 × 2.8 = ½ × 14 = 7 square units."
                },
                {
                    question: "What is the area of a square with side length 7 units?",
                    options: ["14 square units", "28 square units", "49 square units", "21 square units"],
                    correct: 2,
                    explanation: "Area = side² = 7 × 7 = 49 square units."
                },
                {
                    question: "What is the area of a parallelogram with base 5 units and height 12 units?",
                    options: ["60 square units", "17 square units", "34 square units", "7 square units"],
                    correct: 0,
                    explanation: "Area = base × height = 5 × 12 = 60 square units."
                },
                {
                    question: "What is the area of a parallelogram with base 19 units and vertical height 6 units?",
                    options: ["133 square units", "26 square units", "52 square units", "114 square units"],
                    correct: 3,
                    explanation: "Area of a parallelogram = base × height = 19 × 6 = 114 square units."
                }
            ]
        };

        const level3_3 = {
            name: "3.3 Surface Area and Volume",
            background: "bg-castle",
            monsterName: "Crystal Titan",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster9.png",
            monsterHP: 100,
            standard: "6.G.A.2",
            questions: [
                {
                    question: "Which formula is used to find the volume of a prism?",
                    options: ["Base area ÷ Height", "Length × Width × Height", "Length × Width × Perimeter", "Length + Width × Height"],
                    correct: 1,
                    explanation: "The volume of any prism = Length × Width × Height."
                },
                {
                    question: "A solid figure is casting a square shadow. The figure could NOT be a ___.",
                    options: ["Rectangular prism", "Cylinder", "Pentagonal pyramid", "Hexagonal prism"],
                    correct: 2,
                    explanation: "A pentagonal pyramid has 5 sides and cannot cast a square shadow."
                },
                {
                    question: "Which solid figure has the most flat surfaces?",
                    options: ["A cube", "A triangular prism", "A hexagonal prism", "A pentagonal pyramid"],
                    correct: 2,
                    explanation: "A hexagonal prism has 8 faces. A cube has 6, a triangular prism has 5, and a pentagonal pyramid has 6."
                },
                {
                    question: "If each side of a cube measures 4 cm, what is its volume?",
                    options: ["16 cm³", "32 cm³", "48 cm³", "64 cm³"],
                    correct: 3,
                    explanation: "Volume = side³ = 4 × 4 × 4 = 64 cm³."
                },
                {
                    question: "A hexagon must have ___.",
                    options: ["6 sides and 6 angles", "8 sides and 8 angles", "10 sides and 10 angles", "7 sides and 7 angles"],
                    correct: 0,
                    explanation: "A hexagon is a shape with exactly 6 sides and 6 angles."
                },
                {
                    question: "A cube has a volume of 1,000 cm³. What is its surface area?",
                    options: ["100 sq. cm", "60 sq. cm", "600 sq. cm", "It cannot be determined"],
                    correct: 2,
                    explanation: "Side = ∛1000 = 10 cm. Surface area = 6 × side² = 6 × 100 = 600 sq. cm."
                },
                {
                    question: "What happens to the area of a square if the length of its sides is doubled?",
                    options: ["The area doubles.", "The area is halved.", "The area remains unchanged.", "The area quadruples."],
                    correct: 3,
                    explanation: "New area = (2s)² = 4s². The area quadruples when the side length is doubled."
                },
                {
                    question: "A rectangular garden has an area of 432 sq. ft. One side is 24 ft. What is the adjacent side?",
                    options: ["20 feet", "18 feet", "16 feet", "14 feet"],
                    correct: 1,
                    explanation: "A = L × W → 432 = 24 × W → W = 432 ÷ 24 = 18 feet."
                },
                {
                    question: "A marble tile is 25 cm × 20 cm. How many tiles cover a floor of 2 m × 3 m?",
                    options: ["320 tiles", "240 tiles", "180 tiles", "120 tiles"],
                    correct: 3,
                    explanation: "Floor = 200 cm × 300 cm. Tiles across = 300÷25=12, tiles down = 200÷20=10. Total = 12×10 = 120 tiles."
                }
            ]
        };

        const level4_1 = {
            name: "4.1 Whole Number Exponents",
            background: "bg-volcano",
            monsterName: "Exponent Elemental",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster10.png",
            monsterHP: 100,
            standard: "6.EE.A.1",
            questions: [
                {
                    question: "Evaluate: 6³",
                    options: ["18", "216", "9", "3"],
                    correct: 1,
                    explanation: "6³ = 6 × 6 × 6 = 216."
                },
                {
                    question: "Evaluate: 3⁴",
                    options: ["9", "12", "81", "1"],
                    correct: 2,
                    explanation: "3⁴ = 3 × 3 × 3 × 3 = 81."
                },
                {
                    question: "Evaluate: 5³",
                    options: ["15", "125", "8", "2"],
                    correct: 1,
                    explanation: "5³ = 5 × 5 × 5 = 125."
                },
                {
                    question: "Rewrite h³ without using exponents.",
                    options: ["3h", "h + h + h", "h × h × h", "h × h"],
                    correct: 2,
                    explanation: "h³ means h is multiplied 3 times: h × h × h."
                },
                {
                    question: "Write 3 × 3 × 3 × 3 using an exponent.",
                    options: ["3 × 4", "4³", "81", "3⁴"],
                    correct: 3,
                    explanation: "The base is 3, and there are 4 factors. So 3 × 3 × 3 × 3 = 3⁴."
                },
                {
                    question: "Write 2 × 2 × 2 × 2 × 2 × 2 using an exponent.",
                    options: ["2 × 6", "12", "2⁶", "6²"],
                    correct: 2,
                    explanation: "The base is 2, and there are 6 factors. So 2⁶."
                },
                {
                    question: "Write y × y × y × y using an exponent.",
                    options: ["4y", "y⁴", "4 + y", "y + 4"],
                    correct: 1,
                    explanation: "The base is y, and there are 4 factors. So y⁴."
                },
                {
                    question: "Write 13 × 13 × 13 using an exponent.",
                    options: ["2,197", "3 × 13", "39", "13³"],
                    correct: 3,
                    explanation: "The base is 13, and there are 3 factors. So 13³."
                },
                {
                    question: "Find the numerical value of 3².",
                    options: ["6", "5", "9", "1"],
                    correct: 2,
                    explanation: "3² = 3 × 3 = 9."
                },
                {
                    question: "Find the numerical value of 10⁴.",
                    options: ["14", "10,000", "100", "6"],
                    correct: 1,
                    explanation: "10⁴ = 10 × 10 × 10 × 10 = 10,000."
                }
            ]
        };

        const level4_2 = {
            name: "4.2 Unit Rates",
            background: "bg-dragon",
            monsterName: "Rate Wraith",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster11.png",
            monsterHP: 100,
            standard: "6.RP.A.2",
            questions: [
                {
                    question: "Cantaloupes are on sale for 2 for $1.25. What is the unit rate?",
                    options: ["64.5¢/cantaloupe", "63.5¢/cantaloupe", "62.5¢/cantaloupe", "61.5¢/cantaloupe"],
                    correct: 2,
                    explanation: "Divide $1.25 ÷ 2 = $0.625. So the unit rate is 62.5¢ per cantaloupe."
                },
                {
                    question: "Which is a better price: 5 for $1.00, 4 for 85¢, 2 for 25¢, or 6 for $1.10?",
                    options: ["5 for $1.00", "4 for 85¢", "2 for 25¢", "6 for $1.10"],
                    correct: 2,
                    explanation: "Unit prices: $0.20, $0.2125, $0.125, $0.183. 2 for 25¢ gives the lowest unit price of $0.125."
                },
                {
                    question: "A box of 3 picture hangers sells for $2.22. What is the unit rate?",
                    options: ["$1.11 per hanger", "$2.22 per hanger", "74¢ per hanger", "98¢ per hanger"],
                    correct: 2,
                    explanation: "$2.22 ÷ 3 = $0.74 or 74¢ per hanger."
                },
                {
                    question: "Store A: 5 cans for $3.45. Store B: 7 for $5.15. Store C: 4 for $2.46. Store D: 6 for $4.00. Which has the best price?",
                    options: ["Store A", "Store B", "Store C", "Store D"],
                    correct: 2,
                    explanation: "Store C has the lowest unit rate: $2.46 ÷ 4 = $0.615 per can."
                },
                {
                    question: "How much would you save buying 20 cans from Store C ($2.46/4 cans) vs Store A ($3.45/5 cans)?",
                    options: ["$1.75", "$1.25", "$1.50", "95¢"],
                    correct: 2,
                    explanation: "Store A: $0.69 × 20 = $13.80. Store C: $0.615 × 20 = $12.30. Savings: $13.80 − $12.30 = $1.50."
                },
                {
                    question: "Beverly drove 284 miles at a constant speed of 58 mph. How long did it take her?",
                    options: ["4 hours and 45 minutes", "4 hours and 54 minutes", "4 hours and 8 minutes", "4 hours and 89 minutes"],
                    correct: 1,
                    explanation: "284 ÷ 58 ≈ 4.9 hours. 0.9 × 60 = 54 minutes. Answer: 4 hours and 54 minutes."
                },
                {
                    question: "Don earns $7.55/hr (10 hrs) and $8.45/hr (15 hrs). What were his average earnings per hour?",
                    options: ["$8.00", "$8.09", "$8.15", "$8.13"],
                    correct: 1,
                    explanation: "($7.55×10)+($8.45×15) = $75.50+$126.75 = $202.25. $202.25 ÷ 25 = $8.09/hr."
                },
                {
                    question: "Paper towels cost $10.48 for a package of 8 rolls. What is the unit price?",
                    options: ["$1.31 per roll", "$1.05 per roll", "$1.52 per roll", "$1.27 per roll"],
                    correct: 0,
                    explanation: "$10.48 ÷ 8 = $1.31 per roll."
                },
                {
                    question: "It took Marjorie 15 minutes to drive 4 miles to school. What was her speed in mph?",
                    options: ["16 mph", "8 mph", "4 mph", "30 mph"],
                    correct: 0,
                    explanation: "15 minutes = ¼ hour. 4 miles ÷ ¼ hour = 16 mph."
                },
                {
                    question: "Louise walks 2 miles in 30 minutes. At what rate is she walking?",
                    options: ["2 mph", "3 mph", "4 mph", "5 mph"],
                    correct: 2,
                    explanation: "30 minutes = ½ hour. 2 miles ÷ ½ hour = 4 mph."
                }
            ]
        };

        const level4_3 = {
            name: "4.3 Finding Percent",
            background: "bg-forest",
            monsterName: "Percent Phantom",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster12.png",
            monsterHP: 100,
            standard: "6.RP.A.3.C",
            questions: [
                {
                    question: "What is 25% of 24?",
                    options: ["5", "6", "11", "17"],
                    correct: 1,
                    explanation: "x/24 = 25/100 → 100x = 600 → x = 6."
                },
                {
                    question: "What is 15% of 60?",
                    options: ["9", "12", "15", "25"],
                    correct: 0,
                    explanation: "x/60 = 15/100 → 100x = 900 → x = 9."
                },
                {
                    question: "The number 9 is what percent of 72?",
                    options: ["7.2%", "8%", "12.5%", "14%"],
                    correct: 2,
                    explanation: "9/72 = x/100 → 900 = 72x → x = 12.5%."
                },
                {
                    question: "How much is 30% of 190?",
                    options: ["45", "57", "60", "63"],
                    correct: 1,
                    explanation: "x/190 = 30/100 → 100x = 5,700 → x = 57."
                },
                {
                    question: "Daniel has 280 baseball cards. 15% are highly collectable. How many are collectable?",
                    options: ["15 cards", "19 cards", "42 cards", "47 cards"],
                    correct: 2,
                    explanation: "x/280 = 15/100 → 100x = 4,200 → x = 42 cards."
                },
                {
                    question: "The football team consumed 80% of the water provided. If they drank 8 gallons, how much was provided?",
                    options: ["10 gallons", "12 gallons", "15 gallons", "18.75 gallons"],
                    correct: 0,
                    explanation: "8/x = 80/100 → 800 = 80x → x = 10 gallons."
                },
                {
                    question: "Joshua brought 156 of his 678 Legos to Emily's house. What percentage did he bring?",
                    options: ["4%", "23%", "30%", "43%"],
                    correct: 1,
                    explanation: "156/678 = x/100 → 15,600 = 678x → x ≈ 23%."
                },
                {
                    question: "Alexis hit 8 out of 15 balls into the outfield. Which equation finds the percentage?",
                    options: ["15/8 = x/100", "15/100 = x/8", "8x = (100)(15)", "8/15 = x/100"],
                    correct: 3,
                    explanation: "8 out of 15 gives the fraction 8/15. Converting: 8/15 = x/100."
                },
                {
                    question: "Of Nikki's 78 flowers, 32% are roses. Approximately how many roses does she have?",
                    options: ["18 roses", "25 roses", "28 roses", "41 roses"],
                    correct: 1,
                    explanation: "32/100 × 78 = 0.32 × 78 = 24.96 ≈ 25 roses."
                },
                {
                    question: "Victor took out 30% of his paper. Paul used 6, Allison 8, Victor 10 sheets. How many sheets did Victor NOT take out?",
                    options: ["24 sheets", "50 sheets", "56 sheets", "80 sheets"],
                    correct: 2,
                    explanation: "Used = 6+8+10=24. 24/x = 30/100 → x=80 total. Not taken out = 80−24 = 56 sheets."
                }
            ]
        };

        const percentageVolcano = {
            name: "Percentage Volcano",
            background: "bg-volcano",
            monsterName: "Lava Golem",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster1.png",
            monsterHP: 100,
            questions: [
                {
                    question: "What is 50% of 80?",
                    options: ["40", "20", "60", "30"],
                    correct: 0,
                    explanation: "50% means half. Half of 80 is 40."
                },
                {
                    question: "Write 0.75 as a percentage.",
                    options: ["7.5%", "75%", "0.75%", "750%"],
                    correct: 1,
                    explanation: "Multiply by 100. 0.75 * 100 = 75%."
                },
                {
                    question: "What is 10% of 200?",
                    options: ["50", "10", "20", "100"],
                    correct: 2,
                    explanation: "Move the decimal one place left. 200 becomes 20."
                },
                {
                    question: "25% is equivalent to which fraction?",
                    options: ["1/5", "1/2", "1/3", "1/4"],
                    correct: 3,
                    explanation: "25% is 25/100, which simplifies to 1/4."
                },
                {
                    question: "If you scored 90 out of 100, what is your percentage?",
                    options: ["9%", "90%", "0.9%", "99%"],
                    correct: 1,
                    explanation: "90/100 is literally 90 per cent."
                },
                {
                    question: "What is 20% of 50?",
                    options: ["5", "10", "15", "20"],
                    correct: 1,
                    explanation: "10% of 50 is 5. So 20% is 10."
                },
                {
                    question: "If a $40 shirt is 25% off, what is the discount amount?",
                    options: ["$10", "$25", "$5", "$15"],
                    correct: 0,
                    explanation: "25% is 1/4. 1/4 of $40 is $10."
                },
                {
                    question: "Write 3/4 as a percentage.",
                    options: ["34%", "43%", "75%", "25%"],
                    correct: 2,
                    explanation: "3/4 is 0.75, which is 75%."
                },
                {
                    question: "What percentage is 15 out of 60?",
                    options: ["15%", "25%", "60%", "30%"],
                    correct: 1,
                    explanation: "15/60 simplifies to 1/4, which is 25%."
                },
                {
                    question: "What is 100% of 99?",
                    options: ["100", "0", "99", "9.9"],
                    correct: 2,
                    explanation: "100% means the total whole amount, which is 99."
                }
            ]
        };


        const algebraDragon = {
            name: "Algebra Dragon",
            background: "bg-dragon",
            monsterName: "Ancient Dragon",
            monsterSprite: "https://raw.githubusercontent.com/Dhanush127528/images/main/monster1.png",
            monsterHP: 100, // Boss Logic
            isBoss: true,
            questions: [
                {
                    question: "Solve for x: x + 5 = 12",
                    options: ["5", "7", "17", "6"],
                    correct: 1,
                    explanation: "Subtract 5 from both sides. x = 12 - 5 = 7."
                },
                {
                    question: "What is the value of 2x if x = 6?",
                    options: ["8", "12", "3", "36"],
                    correct: 1,
                    explanation: "Substitute x with 6. 2 * 6 = 12."
                },
                {
                    question: "Solve for y: 3y = 15",
                    options: ["3", "15", "5", "45"],
                    correct: 2,
                    explanation: "Divide both sides by 3. y = 15 / 3 = 5."
                },
                {
                    question: "Evaluate 10 - a if a = 4",
                    options: ["6", "14", "40", "5"],
                    correct: 0,
                    explanation: "Substitute a with 4. 10 - 4 = 6."
                },
                {
                    question: "Which equation matches: 'A number plus 3 is 10'?",
                    options: ["n - 3 = 10", "3n = 10", "n + 3 = 10", "n / 3 = 10"],
                    correct: 2,
                    explanation: "Plus means addition. n + 3 = 10."
                },
                {
                    question: "Solve: 2x + 1 = 9",
                    options: ["5", "4", "3", "8"],
                    correct: 1,
                    explanation: "Subtract 1: 2x=8. Divide by 2: x=4."
                },
                {
                    question: "If m/3 = 5, what is m?",
                    options: ["15", "8", "2", "5/3"],
                    correct: 0,
                    explanation: "Multiply both sides by 3. m = 5*3 = 15."
                },
                {
                    question: "Find value of 5 + 3y when y = 2.",
                    options: ["16", "11", "10", "13"],
                    correct: 1,
                    explanation: "Multiply first: 3*2=6. Add 5: 6+5=11."
                },
                {
                    question: "Solve: x - 7 = -2",
                    options: ["5", "-5", "9", "-9"],
                    correct: 0,
                    explanation: "Add 7 to both sides: x = -2 + 7 = 5."
                },
                {
                    question: "Which simplifies to 3x?",
                    options: ["x+x+x", "x+3", "x*x*x", "3+x"],
                    correct: 0,
                    explanation: "Multiplication is repeated addition. x+x+x = 3x."
                }
            ]
        };


        const levels = [level1_1, level1_2, level1_3, level2_1, level2_2, level2_3, level3_1, level3_2, level3_3, level4_1, level4_2, level4_3, percentageVolcano, algebraDragon];

        let gameState = {
            currentLevelIndex: 0, // Start from the beginning
            playerHP: 100,
            maxPlayerHP: 100,
            monsterHP: 100,
            maxMonsterHP: 100,
            score: 0,
            combo: 0,
            currentQuestion: null,
            levelCorrectAnswers: 0,
            levelTotalAnswers: 0,
            availableQuestions: [],
            animating: false,
            levelStartScore: 0,
            completedRanks: {}, // Stores the highest rank achieved per level index
            selectedHero: "https://raw.githubusercontent.com/Dhanush127528/images/main/hero1.png", // Default hero
            selectedHeroName: "Super Boy"
        };

        // Parse the saved game data injected by PHP
        const savedGameDataRaw = '<?php echo $gameData ? addslashes($gameData) : "null"; ?>';
        let savedGameData = null;

        if (savedGameDataRaw !== "null") {
            try {
                savedGameData = JSON.parse(savedGameDataRaw);
                console.log("Loaded previous save data:", savedGameData);

                // Overwrite game variables with saved data
                gameState.currentLevelIndex  = savedGameData.currentLevelIndex  || 0;
                gameState.score              = savedGameData.score              || 0;
                gameState.completedRanks     = savedGameData.completedRanks     || {};
                gameState.selectedHero       = savedGameData.selectedHero       || "https://raw.githubusercontent.com/Dhanush127528/images/main/hero1.png";
                gameState.selectedHeroName   = savedGameData.selectedHeroName   || "Super Boy";

            } catch (e) {
                console.error("Error parsing saved game data", e);
            }
        }

        // ===================== DOM REFS =====================
        const startScreen = document.getElementById('start-screen');
        const heroSelectScreen = document.getElementById('hero-select-screen');
        const mapScreen = document.getElementById('map-screen');
        const battleScreen = document.getElementById('battle-screen');
        const resultScreen = document.getElementById('result-screen');

        const playerHPBar = document.getElementById('player-hp-bar');
        const monsterHPBar = document.getElementById('monster-hp-bar');
        const playerHPText = document.getElementById('player-hp-text');
        const monsterHPText = document.getElementById('monster-hp-text');

        const scoreDisplay = document.getElementById('score-display');
        const comboDisplay = document.getElementById('combo-display');

        // ===================== AUDIO REFS =====================
        const sfxCorrect = document.getElementById('sfx-correct');
        const sfxWrong = document.getElementById('sfx-wrong');
        const sfxHit = document.getElementById('sfx-hit');
        const sfxShoot = document.getElementById('sfx-shoot');
        const sfxWin = document.getElementById('sfx-win');
        const sfxLose = document.getElementById('sfx-lose');

        function playSound(sound) {
            if (!sound) return;
            sound.currentTime = 0;
            sound.play().catch(e => console.log('Audio play failed:', e));
        }

        const heroArea = document.getElementById('hero-area');
        const monsterArea = document.getElementById('monster-area');
        const heroSprite = document.getElementById('hero-sprite');
        const monsterSprite = document.getElementById('monster-sprite');
        const monsterName = document.getElementById('hud-monster-name') || document.getElementById('monster-name');
        const feedbackMsg = document.getElementById('feedback-message');

        const questionText = document.getElementById('question-text');
        const optionsContainer = document.getElementById('options-container');

        // ===================== VISUAL SYNC FROM SAVED DATA =====================
        if (savedGameData !== null) {
            // 1. Update score display
            if (scoreDisplay) scoreDisplay.innerText = gameState.score;

            // 2. Update hero icon on the map button
            const btnIcon = document.getElementById('btn-hero-icon');
            if (btnIcon) btnIcon.src = gameState.selectedHero;

            // 3. Unlock and badge map nodes based on completedRanks
            for (let i = 0; i < 12; i++) {
                const prevRank = i > 0 ? gameState.completedRanks[i - 1] : null;
                const isUnlocked = i === 0 || prevRank === 'A' || prevRank === 'P';
                const node = document.getElementById('mnode-' + i);
                if (!node) continue;

                if (isUnlocked) {
                    node.classList.remove('locked');
                    const lockIcon = node.querySelector('.adv-nlock');
                    if (lockIcon) lockIcon.style.display = 'none';
                    const numLabel = node.querySelector('.adv-nn');
                    if (numLabel) numLabel.style.display = 'block';
                }

                // Add rank badge if level completed
                const rank = gameState.completedRanks[i];
                if (rank) {
                    node.classList.add('completed');
                    const old = node.querySelector('.adv-rank-badge');
                    if (old) old.remove();
                    const badge = document.createElement('div');
                    badge.className = `adv-rank-badge rank-${rank.toLowerCase()}`;
                    badge.textContent = rank;
                    node.appendChild(badge);
                }
            }

            // 4. Show Final Scorecard button if level 11 (4.3) is done
            const scorecardBtn = document.getElementById('btn-final-scorecard');
            if (scorecardBtn && gameState.completedRanks[11]) {
                scorecardBtn.style.display = 'block';
            }

            console.log('UI synced from saved game data.');
        }

        // ===================== NAVIGATION =====================
        function switchScreen(screen) {
            document.querySelectorAll('.screen').forEach(s => {
                s.classList.remove('active');
                s.style.display = 'none';
            });
            screen.style.display = 'flex';
            requestAnimationFrame(() => screen.classList.add('active'));
        }

        function resetGame() {
            gameState.currentLevelIndex = 0;
            gameState.playerHP = 100;
            gameState.score = 0;
            gameState.combo = 0;
            updateMap();
            switchScreen(startScreen);
        }

        function drawMapPath() {
            const canvas = document.getElementById('path-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const world = document.getElementById('hmap-world');
            const worldW = world ? world.offsetWidth : window.innerWidth;
            const worldH = world ? world.scrollHeight : 2600;
            canvas.width = worldW;
            canvas.height = worldH;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.beginPath();
            ctx.setLineDash([18, 12]);
            ctx.lineWidth = 6;
            ctx.strokeStyle = 'rgba(180, 130, 40, 0.65)';
            ctx.lineCap = 'round';

            let first = true;
            let lastX = 0, lastY = 0;
            const canvasRect = canvas.getBoundingClientRect();

            for (let i = 0; i < 12; i++) {
                const node = document.getElementById(`mnode-${i}`);
                if (!node) continue;

                // Use getBoundingClientRect to calculate exact positions regardless of layout wrappers
                const rect = node.getBoundingClientRect();

                // Calculate center point relative to the actual drawing canvas
                const x = rect.left - canvasRect.left + (rect.width / 2);
                const y = rect.top - canvasRect.top + (rect.height / 2);

                if (first) {
                    ctx.moveTo(x, y);
                    first = false;
                } else {
                    // Smooth vertical S-curve
                    const midY = (lastY + y) / 2;
                    ctx.bezierCurveTo(lastX, midY, x, midY, x, y);
                }
                lastX = x;
                lastY = y;
            }
            ctx.stroke();

            // Erase the dashed line precisely where the monsters and nodes are to create a depth effect underneath!
            ctx.globalCompositeOperation = 'destination-out';
            for (let i = 0; i < 12; i++) {
                // 1. Erase precisely around the lock node
                const mnode = document.getElementById(`mnode-${i}`);
                if (mnode) {
                    const rect = mnode.getBoundingClientRect();
                    ctx.beginPath();
                    ctx.arc(
                        rect.left - canvasRect.left + rect.width / 2,
                        rect.top - canvasRect.top + rect.height / 2,
                        55, // 55px radius provides a clean spacing around the 90x90 node
                        0, Math.PI * 2
                    );
                    ctx.fill();
                }

                // 2. Erase precisely around the monster image so line goes behind it
                const nw = document.getElementById(`mnw-${i}`);
                if (nw) {
                    const monster = nw.querySelector('.map-monster');
                    if (monster) {
                        const mRect = monster.getBoundingClientRect();
                        ctx.beginPath();
                        ctx.arc(
                            mRect.left - canvasRect.left + mRect.width / 2,
                            mRect.top - canvasRect.top + mRect.height / 2 + 5,
                            Math.max(mRect.width, mRect.height) / 2.2, // Tighter radius for the monster
                            0, Math.PI * 2
                        );
                        ctx.fill();
                    }
                }
            }
            ctx.globalCompositeOperation = 'source-over'; // Reset mode for next time
        }

        function updateMap() {
            let highestUnlocked = 0;
            // Determine highest unlocked level based on previous ranks
            for (let i = 0; i < 12; i++) {
                const prevRank = i > 0 ? gameState.completedRanks[i - 1] : null;
                if (i === 0 || prevRank === 'A' || prevRank === 'P') {
                    highestUnlocked = i;
                } else {
                    break;
                }
            }

            // Sync currentLevelIndex if they were somehow ahead (e.g. from an old save)
            if (gameState.currentLevelIndex > highestUnlocked) {
                gameState.currentLevelIndex = highestUnlocked;
            }

            for (let i = 0; i < 12; i++) {
                const node = document.getElementById(`mnode-${i}`);
                if (!node) continue;

                const prevRank = i > 0 ? gameState.completedRanks[i - 1] : null;
                const isUnlocked = i === 0 || prevRank === 'A' || prevRank === 'P';

                if (isUnlocked) {
                    node.classList.remove('locked');
                    if (gameState.completedRanks[i]) {
                        node.classList.add('completed');
                    } else {
                        node.classList.remove('completed');
                    }

                    if (i === gameState.currentLevelIndex) {
                        node.classList.add('active');
                    } else {
                        node.classList.remove('active');
                    }
                } else {
                    node.classList.add('locked');
                    node.classList.remove('active', 'completed');
                }

                // Add right side rank badges for horizontal map wrappers
                // Remove existing to prevent duplicates
                const existing = node.querySelector('.adv-rank-badge');
                if (existing) existing.remove();

                if (gameState.completedRanks[i]) {
                    const badge = document.createElement('div');
                    const rank = gameState.completedRanks[i];
                    badge.className = `adv-rank-badge rank-${rank.toLowerCase()}`;
                    badge.textContent = rank;
                    node.appendChild(badge);
                }
            }

            // Check if level 11 (4.3) is completed to show "Final Scorecard" button
            const scorecardBtn = document.getElementById('btn-final-scorecard');
            if (scorecardBtn) {
                if (gameState.completedRanks[11]) {
                    scorecardBtn.style.display = 'block';
                } else {
                    scorecardBtn.style.display = 'none';
                }
            }

            drawMapPath();
        }

        // Draw path on resize or container size changes (like images/fonts loading)
        window.addEventListener('resize', drawMapPath);

        const vmapWorld = document.getElementById('hmap-world');
        if (vmapWorld) {
            new ResizeObserver(() => {
                requestAnimationFrame(() => requestAnimationFrame(drawMapPath));
            }).observe(vmapWorld);
        }

        // ===================== EVENTS =====================
        function updateHeroRoster() {
            // Check Level 6 (index 5) for Hero 3
            const hero3 = document.getElementById('hero-opt-3');
            if (hero3) {
                if (gameState.completedRanks[5] === 'A' || gameState.completedRanks[5] === 'P') {
                    hero3.classList.remove('locked');
                    hero3.style.opacity = '1';
                    hero3.style.filter = 'none';
                    const overlay = hero3.querySelector('.hero-lock-overlay');
                    if (overlay) overlay.style.display = 'none';
                    const reqTxt = document.getElementById('hero-req-3');
                    if (reqTxt) { reqTxt.textContent = 'Unlocked'; reqTxt.style.color = '#43e97b'; }
                }
            }
            // Check Level 9 (index 8) for Hero 4
            const hero4 = document.getElementById('hero-opt-4');
            if (hero4) {
                if (gameState.completedRanks[8] === 'A' || gameState.completedRanks[8] === 'P') {
                    hero4.classList.remove('locked');
                    hero4.style.opacity = '1';
                    hero4.style.filter = 'none';
                    const overlay = hero4.querySelector('.hero-lock-overlay');
                    if (overlay) overlay.style.display = 'none';
                    const reqTxt = document.getElementById('hero-req-4');
                    if (reqTxt) { reqTxt.textContent = 'Unlocked'; reqTxt.style.color = '#43e97b'; }
                }
            }
        }

        // 1. Start btn goes to Hero Select Screen
        document.getElementById('start-btn').addEventListener('click', () => {
            updateHeroRoster();
            switchScreen(heroSelectScreen);
        });

        // 2. Hero Selection Logic
        document.querySelectorAll('.hero-option').forEach(option => {
            option.addEventListener('click', (e) => {
                const target = e.currentTarget;
                if (target.classList.contains('locked')) return; // Prevent selecting locked heroes

                // Remove selected class from all
                document.querySelectorAll('.hero-option').forEach(opt => opt.classList.remove('selected'));
                // Add to clicked
                target.classList.add('selected');

                // Update game state
                gameState.selectedHero = target.dataset.hero;
                gameState.selectedHeroName = target.dataset.name;

                // Update map button icon
                const btnIcon = document.getElementById('btn-hero-icon');
                if (btnIcon) btnIcon.src = gameState.selectedHero;
            });
        });

        // 3. Confirm Hero goes to Map Screen
        document.getElementById('confirm-hero-btn').addEventListener('click', () => {
            saveGameProgressToDynamoDB(); // Save when hero is confirmed/changed
            switchScreen(mapScreen);
            updateMap();
            requestAnimationFrame(() => requestAnimationFrame(drawMapPath));
        });

        document.querySelectorAll('.adv-node').forEach(node => {
            node.addEventListener('click', () => {
                if (!node.classList.contains('locked')) {
                    startLevel(parseInt(node.getAttribute('data-level')));
                }
            });
        });

        document.getElementById('next-level-btn').addEventListener('click', () => {
            if (document.getElementById('next-level-btn').textContent.includes('View Map')) {
                updateMap();
                switchScreen(mapScreen);
                requestAnimationFrame(() => requestAnimationFrame(drawMapPath));
                return;
            }
            if (gameState.currentLevelIndex < levels.length - 1) {
                gameState.currentLevelIndex++;
                saveGameProgressToDynamoDB(); // Save after advancing to next level
                updateMap();
                switchScreen(mapScreen);
                requestAnimationFrame(() => requestAnimationFrame(drawMapPath));
            } else {
                // If it's the final level, "Play Again" means a full reset
                resetGame();
            }
        });

        document.getElementById('restart-btn').addEventListener('click', () => {
            startLevel(gameState.currentLevelIndex);
        });

        document.getElementById('btn-choose-hero').addEventListener('click', () => {
            updateHeroRoster();
            switchScreen(heroSelectScreen);
        });

        function showFinalScoreCard() {
            const list = document.getElementById('scorecard-list');
            list.innerHTML = ''; // clear

            for (let i = 0; i < 12; i++) {
                const level = levels[i];
                const rank = gameState.completedRanks[i] || 'None';
                const row = document.createElement('div');
                row.className = 'score-row';
                
                let rankClass = '';
                let rankLabel = 'Not Played';
                if(rank === 'A') { rankClass = 'rank-a'; rankLabel = 'Advanced (A)'; row.style.borderLeftColor = '#43e97b'; }
                else if(rank === 'P') { rankClass = 'rank-p'; rankLabel = 'Proficient (P)'; row.style.borderLeftColor = '#ffd200'; }
                else if(rank === 'PP') { rankClass = 'rank-pp'; rankLabel = 'Partially Proficient (PP)'; row.style.borderLeftColor = '#ff5f6d'; }

                const standard = level.standard ? level.standard : 'No Standard';

                row.innerHTML = `
                    <div class="score-level-name">${level.name}</div>
                    <div class="score-standard">${standard}</div>
                    <div class="score-rank ${rankClass}">${rankLabel}</div>
                `;
                list.appendChild(row);
            }

            switchScreen(document.getElementById('final-score-screen'));
        }

        document.getElementById('btn-final-scorecard').addEventListener('click', showFinalScoreCard);
        document.getElementById('btn-back-to-map').addEventListener('click', () => {
            updateMap();
            switchScreen(mapScreen);
            requestAnimationFrame(() => requestAnimationFrame(drawMapPath));
        });

        // Drag-to-pan for vertical map
        (function () {
            const el = document.getElementById('hmap-scroll');
            if (!el) return;
            let isDown = false, startY, scrollTop;
            el.addEventListener('mousedown', e => {
                if (e.target.closest('.adv-node')) return;
                isDown = true; el.style.cursor = 'grabbing';
                startY = e.pageY - el.offsetTop; scrollTop = el.scrollTop;
            });
            el.addEventListener('mouseleave', () => { isDown = false; el.style.cursor = ''; });
            el.addEventListener('mouseup', () => { isDown = false; el.style.cursor = ''; });
            el.addEventListener('mousemove', e => {
                if (!isDown) return; e.preventDefault();
                el.scrollTop = scrollTop - (e.pageY - el.offsetTop - startY) * 1.5;
            });
            // Touch support
            el.addEventListener('touchstart', e => {
                if (e.target.closest('.adv-node')) return;
                isDown = true; startY = e.touches[0].pageY - el.offsetTop; scrollTop = el.scrollTop;
            }, { passive: true });
            el.addEventListener('touchend', () => { isDown = false; });
            el.addEventListener('touchmove', e => {
                if (!isDown) return;
                el.scrollTop = scrollTop - (e.touches[0].pageY - el.offsetTop - startY) * 1.5;
            }, { passive: true });
        })();

        window.runAway = function () {
            document.getElementById('exit-confirm-modal').style.display = 'flex';
        };

        window.confirmRunAway = function (confirmed) {
            document.getElementById('exit-confirm-modal').style.display = 'none';
            if (confirmed) {
                // Stop any ongoing animations
                gameState.animating = false;
                gameState.score = gameState.levelStartScore ?? gameState.score;
                gameState.combo = 0;
                // Close any open modals
                const modal = document.getElementById('explanation-modal');
                if (modal) modal.style.display = 'none';
                updateMap();
                switchScreen(mapScreen);
                requestAnimationFrame(() => requestAnimationFrame(drawMapPath));
            }
        };

        // ===================== BATTLE =====================
        function startLevel(index) {
            gameState.currentLevelIndex = index;
            gameState.animating = false;
            const level = levels[index];

            gameState.maxMonsterHP = level.monsterHP;
            gameState.monsterHP = level.monsterHP;
            gameState.playerHP = 100;
            gameState.levelCorrectAnswers = 0;
            gameState.levelTotalAnswers = 0;
            gameState.levelStartScore = gameState.score;
            gameState.combo = 0; // Reset combo for new level
            gameState.availableQuestions = [...Array(level.questions.length).keys()]; // Reset question pool

            // Apply selected Hero
            heroSprite.src = gameState.selectedHero;
            document.getElementById('hero-name').textContent = gameState.selectedHeroName;

            // Set background theme
            battleScreen.className = `screen bg-image ${level.background}`;

            // Set monster appearance
            const hudMonsterName = document.getElementById('hud-monster-name');
            if (hudMonsterName) hudMonsterName.textContent = level.monsterName;
            const arenaMonsterName = document.getElementById('monster-name');
            if (arenaMonsterName) arenaMonsterName.textContent = level.monsterName;
            monsterSprite.src = level.monsterSprite;

            // Reset cinematic styles
            heroSprite.className = '';
            heroSprite.style.filter = '';
            heroSprite.style.transform = '';
            monsterSprite.style.opacity = '1';
            monsterSprite.style.filter = 'none';
            monsterSprite.style.transform = 'scale(1)';

            // Display Standard Badge if available
            const standardBadge = document.getElementById('standard-badge');
            if (standardBadge) {
                if (level.standard) {
                    standardBadge.textContent = level.standard;
                    standardBadge.style.display = 'block';
                } else {
                    standardBadge.style.display = 'none';
                }
            }

            updateUI();
            switchScreen(battleScreen);
            generateQuestion();
        }

        function updateUI() {
            const pPct = (gameState.playerHP / gameState.maxPlayerHP) * 100;
            const mPct = (gameState.monsterHP / gameState.maxMonsterHP) * 100;

            playerHPBar.style.width = `${pPct}%`;
            if (playerHPText) playerHPText.textContent = `${gameState.playerHP}/${gameState.maxPlayerHP}`;

            // Change HP bar color dynamically based on health
            playerHPBar.style.background = pPct > 50
                ? 'linear-gradient(90deg,#4facfe,#00f2fe)'
                : pPct > 20
                    ? 'linear-gradient(90deg,#f7971e,#ffd200)'
                    : 'linear-gradient(90deg,#ff5f6d,#ff9a9e)';

            // Pulse when low
            if (pPct <= 20) {
                playerHPBar.parentElement.style.boxShadow = '0 0 15px rgba(255, 95, 109, 0.8)';
            } else {
                playerHPBar.parentElement.style.boxShadow = 'inset 0 2px 5px rgba(0,0,0,0.8)';
            }

            monsterHPBar.style.width = `${mPct}%`;
            if (monsterHPText) monsterHPText.textContent = `${gameState.monsterHP}/${gameState.maxMonsterHP}`;

            scoreDisplay.textContent = gameState.score;
            comboDisplay.textContent = gameState.combo;
        }

        function generateQuestion() {
            const level = levels[gameState.currentLevelIndex];

            // If empty, generate array of indices
            if (gameState.availableQuestions.length === 0) {
                gameState.availableQuestions = [...Array(level.questions.length).keys()];
            }

            const randIdx = Math.floor(Math.random() * gameState.availableQuestions.length);
            const qIdx = gameState.availableQuestions.splice(randIdx, 1)[0];

            // qIdx is the actual index of the question in the level.questions array
            gameState.currentQuestion = level.questions[qIdx];

            questionText.textContent = gameState.currentQuestion.question;
            optionsContainer.innerHTML = '';

            // Remove old hints
            optionsContainer.parentElement.querySelectorAll('.feedback-hint')
                .forEach(el => el.remove());

            gameState.currentQuestion.options.forEach((opt, i) => {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.textContent = opt;
                btn.onclick = () => checkAnswer(i, btn);
                optionsContainer.appendChild(btn);
            });
        }

        // ===================== ANSWER CHECK =====================
        function checkAnswer(selectedIndex, btnElement) {
            if (gameState.animating) return;
            gameState.animating = true;

            const allBtns = optionsContainer.querySelectorAll('button');
            allBtns.forEach(b => b.disabled = true);

            const isCorrect = selectedIndex === gameState.currentQuestion.correct;
            gameState.levelTotalAnswers++;

            if (isCorrect) {
                playSound(sfxCorrect);
                const damage = 25;
                const scoreGain = 10;
                const isLethal = (gameState.monsterHP - damage <= 0);

                btnElement.classList.add('correct');

                // Hero Attack Animation First -> Then Projectile -> Then Hit
                playHeroAttack(() => {
                    playSound(sfxShoot);
                    animateProjectile(heroSprite, monsterSprite, 'bullet', () => {
                        playSound(sfxHit);
                        gameState.monsterHP = Math.max(0, gameState.monsterHP - damage);
                        gameState.score += scoreGain;
                        gameState.combo++;
                        gameState.levelCorrectAnswers++;

                        // Screen slight zoom-in for impact
                        battleScreen.style.transform = 'scale(1.02)';
                        setTimeout(() => battleScreen.style.transform = 'scale(1)', 300);

                        // Combo bonus / Light burst
                        if (gameState.combo % 3 === 0) {
                            gameState.score += 20;
                            showFeedback(`🔥 COMBO x${gameState.combo}! +20`, 'crit-hit');
                            battleScreen.classList.add('screen-flash-white'); // Added cinematic effect
                            setTimeout(() => battleScreen.classList.remove('screen-flash-white'), 300);
                        } else {
                            showFeedback('⚔️ Critical Hit!', 'crit-hit');
                        }

                        spawnParticles(monsterArea, true);
                        playMonsterHit();
                        spawnDamageNumber(monsterArea, `−${damage}`, false);
                        updateUI();

                        checkBattleEnd(true);
                    }, isLethal);
                });

            } else {
                playSound(sfxWrong);
                const damage = levels[gameState.currentLevelIndex].isBoss ? 25 : 20;
                const isLethal = (gameState.playerHP - damage <= 0);
                gameState.combo = 0;

                showFeedback('💨 Miss!', 'miss');

                btnElement.classList.add('wrong');
                allBtns[gameState.currentQuestion.correct].classList.add('correct');

                // Enemy attack -> Projectile -> Hero hit
                playEnemyAttack(() => {
                    playSound(sfxShoot);
                    animateProjectile(monsterSprite, heroSprite, 'fireball', () => {
                        playSound(sfxHit);
                        gameState.playerHP = Math.max(0, gameState.playerHP - damage);
                        playHeroHurt();
                        spawnDamageNumber(heroArea, `−${damage}`, true);
                        spawnParticles(heroArea, false);

                        // Red Screen flash & shake
                        battleScreen.classList.add('screen-shake', 'screen-flash-red');
                        setTimeout(() => battleScreen.classList.remove('screen-shake', 'screen-flash-red'), 500);
                        updateUI();

                        checkBattleEnd(false);
                    }, isLethal);
                });
            }
        }

        function checkBattleEnd(isCorrect) {
            setTimeout(() => {
                showExplanationModal(isCorrect);
            }, 1000);
        }

        function showExplanationModal(isCorrect) {
            const modal = document.getElementById('explanation-modal');
            const title = document.getElementById('explanation-title');
            const body = document.getElementById('explanation-body');
            const nextBtn = document.getElementById('btn-next-encounter');

            title.textContent = isCorrect ? 'Correct! 🎉' : 'Incorrect ❌';
            title.style.color = isCorrect ? '#43e97b' : '#ff5f6d';
            body.textContent = gameState.currentQuestion.explanation;

            if (gameState.playerHP <= 0) {
                nextBtn.innerHTML = 'Sorry, you lost the battle';
                nextBtn.style.background = 'linear-gradient(135deg, #ff5f6d, #ff9a9e)';
            } else if (gameState.monsterHP <= 0) {
                nextBtn.innerHTML = 'Congratulations!🏆';
                nextBtn.style.background = 'linear-gradient(135deg, #43e97b, #38f9d7)';
            } else {
                nextBtn.innerHTML = 'Next Question ➡️';
                nextBtn.style.background = 'linear-gradient(135deg, #4facfe, #00f2fe)';
            }

            modal.style.display = 'flex';
        }

        function continueAfterExplanation() {
            const modal = document.getElementById('explanation-modal');
            modal.style.display = 'none';

            gameState.animating = false;
            if (gameState.playerHP <= 0) {
                // playSound(sfxLose); // Removed as requested
                heroSprite.style.filter = 'grayscale(100%) brightness(0.5)';
                heroSprite.style.transform = 'translateY(30px) scale(0.9)'; // Hero kneels
                setTimeout(() => endLevel(false), 1000);
            } else if (gameState.monsterHP <= 0) {
                playSound(sfxWin);
                monsterSprite.style.opacity = '0';
                monsterSprite.style.transform = 'scale(0.5)';
                heroSprite.style.filter = 'drop-shadow(0 0 50px #ffd200) brightness(1.5)';
                if (levels[gameState.currentLevelIndex].isBoss) gameState.score += 50;
                setTimeout(() => endLevel(true), 1500);
            } else {
                generateQuestion();
            }
        }

        // ===================== ANIMATIONS =====================

        /** Hero lunges toward enemy, callback fires at peak. */
        function playHeroAttack(onPeak) {
            heroSprite.classList.add('hero-attack');
            if (onPeak) setTimeout(onPeak, 200);
            heroSprite.addEventListener('animationend', () => {
                heroSprite.classList.remove('hero-attack');
            }, { once: true });
        }

        /** Monster flashes red + shakes on hit. */
        function playMonsterHit() {
            monsterSprite.classList.add('monster-hit');
            monsterSprite.addEventListener('animationend', () => {
                monsterSprite.classList.remove('monster-hit');
            }, { once: true });
        }

        /** Enemy lunges toward hero, callback fires at peak. */
        function playEnemyAttack(onPeak) {
            monsterSprite.classList.add('enemy-attack');
            if (onPeak) setTimeout(onPeak, 200);
            monsterSprite.addEventListener('animationend', () => {
                monsterSprite.classList.remove('enemy-attack');
            }, { once: true });
        }

        /** Hero shakes red on being hit. */
        function playHeroHurt() {
            heroSprite.classList.add('hero-hurt');
            heroSprite.addEventListener('animationend', () => {
                heroSprite.classList.remove('hero-hurt');
            }, { once: true });
        }

        /**
         * Creates and animates a projectile from a start element to an end element.
         * @param {HTMLElement} startEl - Source element
         * @param {HTMLElement} endEl - Target element
         * @param {string} type - 'bullet' or 'fireball'
         * @param {function} onComplete - Callback when animation finishes
         * @param {boolean} isLethal - Triggers cinematic slow-motion if true
         */
        function animateProjectile(startEl, endEl, type, onComplete, isLethal) {
            const arena = document.querySelector('.battle-arena');
            const arenaRect = arena.getBoundingClientRect();

            const startRect = startEl.getBoundingClientRect();
            const endRect = endEl.getBoundingClientRect();

            const proj = document.createElement('div');
            proj.className = `projectile ${type}`;

            // Initial position (center of start element) relative to arena
            const startX = startRect.left - arenaRect.left + startRect.width / 2 - 15;
            const startY = startRect.top - arenaRect.top + startRect.height / 2 - 15;

            // Target position (center of end element) relative to arena
            const targetX = endRect.left - arenaRect.left + endRect.width / 2 - 15;
            const targetY = endRect.top - arenaRect.top + endRect.height / 2 - 15;

            proj.style.left = `${startX}px`;
            proj.style.top = `${startY}px`;

            arena.appendChild(proj);

            // Trail effect using box-shadow in CSS handles the trail automatically, 
            // but slow speed emphasizes it.

            // Cinematic final stretch slow-motion
            const durationMS = isLethal ? 1000 : 400;

            // Animate using Web Animations API
            const animation = proj.animate([
                { transform: 'translate(0, 0)' },
                { transform: `translate(${targetX - startX}px, ${targetY - startY}px)` }
            ], {
                duration: durationMS,
                easing: isLethal ? 'cubic-bezier(.17,.84,.44,1)' : 'ease-in-out' // Slows down near the end if lethal
            });

            animation.onfinish = () => {
                proj.remove();
                if (onComplete) onComplete();
            };
        }

        /**
         * Spawns a floating damage/heal number near a target element.
         * @param {HTMLElement} target  - Element to position near
         * @param {string}      text    - Text to display e.g. "−25"
         * @param {boolean}     isNeg   - Red if true, green if false
         */
        function spawnDamageNumber(target, text, isNeg) {
            const num = document.createElement('div');
            num.className = `damage-number ${isNeg ? 'negative' : 'positive'}`;
            num.textContent = text;

            const rect = target.getBoundingClientRect();
            const arenaRect = document.querySelector('.battle-arena').getBoundingClientRect();

            // Position relative to arena
            num.style.left = `${rect.left - arenaRect.left + rect.width / 2 - 30}px`;
            num.style.top = `${rect.top - arenaRect.top + 10}px`;
            num.style.position = 'absolute';

            document.querySelector('.battle-arena').appendChild(num);
            num.addEventListener('animationend', () => num.remove(), { once: true });
        }

        /**
         * Spawns coloured spark particles from a target element.
         * @param {HTMLElement} target
         * @param {boolean}     isSuccess - gold sparks if true
         */
        function spawnParticles(target, isSuccess) {
            const arena = document.querySelector('.battle-arena');
            const rect = target.getBoundingClientRect();
            const arenaRect = arena.getBoundingClientRect();

            const colors = isSuccess
                ? ['#ffeb3b', '#ffc107', '#4facfe', '#ffffff', '#00f2fe'] // Electric yellow/blue
                : ['#ff5f6d', '#ff9a9e', '#fff', '#f7971e'];

            for (let i = 0; i < 10; i++) {
                const p = document.createElement('div');
                p.className = 'particle';

                const angle = (i / 10) * 360;
                const dist = 50 + Math.random() * 60;
                const tx = Math.cos((angle * Math.PI) / 180) * dist;
                const ty = Math.sin((angle * Math.PI) / 180) * dist;

                p.style.setProperty('--tx', `translate(${tx}px, ${ty}px)`);
                p.style.background = colors[Math.floor(Math.random() * colors.length)];
                p.style.width = `${6 + Math.random() * 8}px`;
                p.style.height = p.style.width;
                p.style.left = `${rect.left - arenaRect.left + rect.width / 2}px`;
                p.style.top = `${rect.top - arenaRect.top + rect.height / 2}px`;
                p.style.animationDelay = `${i * 30}ms`;

                arena.appendChild(p);
                p.addEventListener('animationend', () => p.remove(), { once: true });
            }
        }

        // ===================== FEEDBACK =====================
        function showFeedback(text, type) {
            feedbackMsg.textContent = text;
            feedbackMsg.className = `feedback-msg show ${type}`;
            setTimeout(() => {
                feedbackMsg.classList.remove('show');
            }, 1500);
        }

        // ===================== LEVEL END =====================
        function endLevel(victory) {
            const accuracy = gameState.levelTotalAnswers > 0
                ? Math.round((gameState.levelCorrectAnswers / gameState.levelTotalAnswers) * 100)
                : 0;

            const rankData = getRank(accuracy);

            document.getElementById('final-score').textContent = gameState.score;
            document.getElementById('final-accuracy').textContent = `${accuracy}%`;

            // Always store the letter rank for the map even on defeat
            const currentRankLetter = rankData.letter;
            const existingRank = gameState.completedRanks[gameState.currentLevelIndex];

            // Rank upgrade logic (A > P > PP)
            const rankOrder = { 'A': 3, 'P': 2, 'PP': 1 };
            if (!existingRank || rankOrder[currentRankLetter] > rankOrder[existingRank]) {
                gameState.completedRanks[gameState.currentLevelIndex] = currentRankLetter;
            }

            if (victory) {
                document.getElementById('final-rank').textContent = rankData.title;

                document.getElementById('result-title').textContent = 'Level Complete! 🏆';
                document.getElementById('result-message').textContent = 'You defeated the monster!';
                document.getElementById('next-level-btn').style.display = 'inline-block';

                // Final level handling
                if (gameState.currentLevelIndex === levels.length - 1) {
                    document.getElementById('next-level-btn').textContent = 'Play Again 🔄';
                    document.getElementById('restart-btn').style.display = 'none'; // Hide duplicate retry
                    document.getElementById('result-title').textContent = 'Adventure Complete! 🎉';
                    document.getElementById('result-message').textContent = 'You saved the Math Kingdom!';
                } else {
                    document.getElementById('next-level-btn').textContent = 'Next Level ➡️';
                    document.getElementById('restart-btn').style.display = 'inline-block';
                }
            } else {
                document.getElementById('final-rank').textContent = `❌ Failed (Rank: ${rankData.title})`;
                document.getElementById('result-title').textContent = 'Game Over 💀';
                document.getElementById('result-message').textContent = 'You ran out of HP! Train harder!';
                document.getElementById('next-level-btn').style.display = 'inline-block';
                document.getElementById('next-level-btn').textContent = 'View Map 🗺️';
                document.getElementById('restart-btn').style.display = 'inline-block';
            }

            // Save progress to DynamoDB after every level end
            saveGameProgressToDynamoDB();

            switchScreen(resultScreen);
        }

        function getRank(accuracy) {
            if (accuracy >= 80) return { title: '🌟 Advanced', letter: 'A' };
            if (accuracy >= 60) return { title: '⚔️ Proficient', letter: 'P' };
            return { title: '🛡️ Partial Proficient', letter: 'PP' };
        }

        // ===================== SAVE TO DYNAMODB =====================
        function saveGameProgressToDynamoDB() {
            // Build a JSON snapshot of everything to remember
            const gameDataToSave = {
                currentLevelIndex: gameState.currentLevelIndex,
                score:             gameState.score,
                completedRanks:    gameState.completedRanks,
                selectedHero:      gameState.selectedHero,
                selectedHeroName:  gameState.selectedHeroName
            };

            // Send the data to the PHP handler at the top of this file
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_progress',
                    gameData: gameDataToSave
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('✅ Progress saved to DynamoDB!');
                } else {
                    console.error('❌ Save failed:', data.message);
                }
            })
            .catch(error => console.error('Error saving progress:', error));
        }

    </script>

</body>

</html>