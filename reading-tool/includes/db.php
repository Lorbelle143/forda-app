<?php
$host     = 'localhost';
$dbname   = 'forda_app';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // ── Users ──────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','student') DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // ── Reading Materials ──────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reading_materials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
            session_number TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-6 (maps to AIM sessions)',
            reading_number TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Reading 1, 2, 3... within a session',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    // ── Recordings ─────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS recordings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            material_id INT NOT NULL,
            audio_path VARCHAR(255) NOT NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            feedback TEXT DEFAULT NULL,
            feedback_at TIMESTAMP NULL,
            feedback_by INT NULL,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (material_id) REFERENCES reading_materials(id) ON DELETE CASCADE,
            FOREIGN KEY (feedback_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    // ── Migrations: add columns if missing ─────────────────
    $alterations = [
        "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE reading_materials ADD COLUMN session_number TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-6'",
        "ALTER TABLE reading_materials ADD COLUMN reading_number TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Reading 1,2,3...'",
        "ALTER TABLE reading_materials ADD COLUMN model_audio_path VARCHAR(255) DEFAULT NULL COMMENT 'Facilitator-uploaded pronunciation model audio'",
        "ALTER TABLE recordings ADD COLUMN mispronounced_count TINYINT UNSIGNED DEFAULT NULL COMMENT 'Student self-reported mispronounced word count'",
        "ALTER TABLE recordings ADD COLUMN milestone VARCHAR(50) DEFAULT NULL COMMENT 'Excellent/Great Progress/Nice Job/Brave Start'",
    ];
    foreach ($alterations as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* column already exists */ }
    }

    // ── Seed admin / student accounts (only if table is empty) ────────────
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $accounts = [
            ['Lorbelle Ganzan', 'lorbelleganzan@gmail.com', 'admin123',   'admin'],
            ['Admin Group 2',   'admin.group2@gmail.com',   'admin123',   'admin'],
            ['Admin Group 3',   'admin.group3@gmail.com',   'admin123',   'admin'],
            ['Admin Group 4',   'admin.group4@gmail.com',   'admin123',   'admin'],
            ['Admin Group 5',   'admin.group5@gmail.com',   'admin123',   'admin'],
            ['Jane Student',    'student@gmail.com',         'student123', 'student'],
        ];
        $ins = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        foreach ($accounts as [$name, $email, $plain, $role]) {
            $ins->execute([$name, $email, password_hash($plain, PASSWORD_BCRYPT), $role]);
        }
    }

    // ── Seed / sync reading materials ─────────────────────
    // Each seed entry: [session, reading, level, title, content]
    $seeds = [
        // WEEK 1 · Session 1 — The Baseline (Easy)
        [1, 1, 'beginner', 'The Baseline — Reading 1',
         'One morning, a cat sat on a mat near a fan while a man held a pen and a pan on the table. The sun shone brightly as a dog ran past a hut and stopped by a cup of milk. The big red bag was beside a box, and the children said, "The cat is on the mat," while looking at the small fish in the bowl.'],
        [1, 2, 'beginner', 'The Baseline — Reading 2',
         'A fat cat had a red pet hen. The hen sat in a big box in the sun. The cat and the hen saw a cup on a wet mat. "Look at the cup!" said the man. They ran to the hut to get some jam and a bun for a snack.'],
        [1, 3, 'beginner', 'The Baseline — Reading 3',
         'The ten men got on a big bus to go to the top of the hill. It was hot, so they took a can of pop and a net. A bad bug bit a kid on the leg, but he did not get mad. He just sat on a log and had a bit of an egg for lunch.'],
        [1, 4, 'beginner', 'The Baseline — Reading 4',
         'Ben has a six-inch rod to fish in the mud. He set his bag on a rock and fed a duck a crumb. The sun was up, and the pot on the fire was hot. Ben hit a bell when it was time to eat his ham and fig in the den.'],
        [1, 5, 'beginner', 'The Baseline — Reading 5',
         'A pig in a wig did a jig on a rug. A fox in a red cap fed the pig a nut. The pig was glad and ran to a tub of wet mud. "It is fun in the sun!" the pig said to a dot on the map while sitting in the flat van.'],

        // WEEK 1 · Session 2 — The Bridge (Easy)
        [2, 1, 'beginner', 'The Bridge — Reading 1',
         'Later that day, a group of friends walked along a quiet path toward a nearby park, where they saw a sheep resting near a small ship by the shore. A boy carried a bag, while another held a map and pointed toward a lamp under a tree. They continued walking and talked about the warm sun, the soft wind, and the calm sea moving gently in the distance.'],
        [2, 2, 'beginner', 'The Bridge — Reading 2',
         'The sheep was still as it stood on the hill. Tim felt a keen need to reach for the green leaf on the thin branch. He took a sip of water and felt the deep chill of the air. Along the path, he saw a fish leap from the thick sea water near the beach.'],
        [2, 3, 'beginner', 'The Bridge — Reading 3',
         'Jill saw a tiny beet near her feet. She had to pick it up before the ship left the dock. The wind was shrill, but she felt peace as she looked at the rich green field. "Please keep the tin lid shut," she said, as she hit the path to go back to the team.'],
        [2, 4, 'beginner', 'The Bridge — Reading 4',
         'A thick mist hung over the deep pit. Lee had a win when he found the key on the shelf. He felt a sting on his cheek, but he did not weep. He sat on a seat to read his speech about the wealth of the earth and the beauty of the sea.'],
        [2, 5, 'beginner', 'The Bridge — Reading 5',
         'A thick mist hung over the deep pit. Lee had a win when he found the key on the shelf. He felt a sting on his cheek, but he did not weep. He sat on a seat to read his speech about the wealth of the earth and the beauty of the sea.'],

        // WEEK 2 · Session 3 — The Shift (Medium)
        [3, 1, 'intermediate', 'The Shift — Reading 1',
         'In the afternoon, the group arrived at a small village where narrow streets were filled with people moving between a market, a library, and a quiet garden surrounded by tall plants. Conversations from different families could be heard as vendors arranged their goods, while visitors observed the environment and slowly explored the area. The atmosphere was lively yet organized, with voices blending together as the day progressed. The children paused near a fountain, watching the water flow steadily while birds rested on nearby branches. A local musician played a soft melody that echoed gently across the open space.'],
        [3, 2, 'intermediate', 'The Shift — Reading 2',
         'The students gathered in the center of the museum to look at the ancient objects. Their teacher gave a brief lecture about the history of the region. Everyone listened carefully as they wandered through the gallery. It was a wonderful opportunity to learn about the culture of the people who lived there long ago.'],
        [3, 3, 'intermediate', 'The Shift — Reading 3',
         'Working in the garden requires a lot of patience and energy. Yesterday, we planted several vegetables and flowers near the entrance. The neighbors often compliment the colors of the blossoms. It is important to water them regularly so they can blossom into a beautiful display for the entire community.'],
        [3, 4, 'intermediate', 'The Shift — Reading 4',
         'The company held a special celebration for the workers in the factory. They organized a party with music and delicious food to recognize the performance of the team. The manager gave a confident message about the future of the business. Everyone felt happy and positive about the success they achieved together.'],
        [3, 5, 'intermediate', 'The Shift — Reading 5',
         'Exploring the jungle is a dangerous but exciting adventure. Travelers must be cautious of the unusual creatures and the slippery surfaces. A knowledgeable guide can help navigate the forest safely. Even in the middle of a thunderstorm, the scenery remains absolutely stunning and memorable.'],

        // WEEK 2 · Session 4 — The Flow (Medium)
        [4, 1, 'intermediate', 'The Flow — Reading 1',
         'As the sun began to set, the travelers continued their journey through a winding road that passed through hills covered with trees and fields that stretched far into the horizon. The changing colors of the sky reflected shades of orange, pink, and violet, creating a scene that seemed calm and continuous. Along the way, they noticed how the movement of air affected the leaves, while distant sounds gradually faded as they moved further from the populated areas. A quiet stream ran beside the road, reflecting the colors of the sky as small ripples formed on its surface. They stopped briefly to observe the scenery before continuing their walk along the path.'],
        [4, 2, 'intermediate', 'The Flow — Reading 2',
         'The hikers reached the summit just as the morning mist cleared, revealing a vast valley that looked peaceful and untouched. Below them, the forest sparkled with dew, while a few birds circled high above the emerald canopy. They felt a sense of wonder as they glimpsed the distant peaks, noticing how the sunlight danced across the jagged rocks. After a short rest, they gathered their gear and began the descent, following a trail that twisted through the ancient pines.'],
        [4, 3, 'intermediate', 'The Flow — Reading 3',
         'A light breeze whispered through the open windows, carrying the sweet scent of jasmine and freshly cut grass into the room. On the wooden table, a single candle flickered, casting long shadows that moved gracefully against the white walls. Outside, the world seemed to slow down as the stars appeared one by one in the dark velvet sky. It was a perfect moment to reflect on the events of the week, listening to the rhythmic chirping of crickets in the garden.'],
        [4, 4, 'intermediate', 'The Flow — Reading 4',
         'The old library was filled with the smell of aged paper and leather, creating an atmosphere that felt both mysterious and welcoming. Rows of books stretched toward the high ceiling, where golden light filtered through the dusty glass panes. A young girl explored the narrow aisles, carefully touching the spines of novels that told stories of forgotten kingdoms and brave explorers. She finally selected a heavy volume, sat in a velvet chair, and allowed herself to be transported to another world.'],
        [4, 5, 'intermediate', 'The Flow — Reading 5',
         'Raindrops tapped gently against the roof, creating a steady beat that matched the calm mood of the rainy afternoon. The streets were nearly empty, except for a few people who hurried home under colorful umbrellas that brightened the gray sidewalk. Inside the cafe, the steam from hot coffee drifted upward, blending with the soft chatter of customers who enjoyed the cozy warmth. Despite the storm outside, the environment remained pleasant and quiet, offering a brief escape from the busy city life.'],

        // WEEK 3 · Session 5 — The Challenge (Advanced)
        [5, 1, 'advanced', 'The Challenge — Reading 1',
         'During the quiet evening, the group settled in a small inn where the environment combined simplicity and comfort, with wooden interiors, soft lighting, and a steady sense of stillness that surrounded the place. Inside, they engaged in conversation, describing the locations they had visited, the individuals they had encountered, and the sequence of events that defined their travel experience. Their discussion flowed continuously, with each sentence containing multiple ideas connected through careful expression and detailed description. The group also shared reflections about the changing environments they observed, noting how each place contributed to the overall experience of their journey. Outside, the night grew darker as stars gradually became visible above the calm surroundings.'],
        [5, 2, 'advanced', 'The Challenge — Reading 2',
         'The historical significance of the architectural site remains a primary focus for researchers investigating ancient civilizations. Various structures within the complex demonstrate a sophisticated understanding of engineering and artistic expression. Scholars have observed that the meticulous arrangement of stone pillars corresponds with astronomical patterns, suggesting a deep connection between the community and the cosmos. Furthermore, the preservation of these artifacts provides an invaluable opportunity for students to analyze the cultural evolution of the region over several centuries.'],
        [5, 3, 'advanced', 'The Challenge — Reading 3',
         'Effective communication within a professional environment requires both clarity of thought and a deliberate choice of vocabulary. Individuals who demonstrate a high level of linguistic accuracy are often more successful in conveying complex information to diverse audiences. It is essential to recognize that the systematic application of phonetic principles contributes significantly to the overall impact of a presentation. By maintaining a consistent rhythm and appropriate emphasis on key terms, a speaker can ensure that their message is both persuasive and authoritative.'],
        [5, 4, 'advanced', 'The Challenge — Reading 4',
         'The implementation of sustainable energy solutions is a critical component of global efforts to mitigate the environmental impact of industrialization. Scientists and policymakers are collaborating to develop innovative technologies that utilize renewable resources, such as solar and wind power, more efficiently. This transition necessitates a comprehensive evaluation of current infrastructure and a commitment to long-term ecological stability. As society progresses toward a greener future, the integration of these systems will play a pivotal role in maintaining the delicate balance of our planet\'s ecosystem.'],
        [5, 5, 'advanced', 'The Challenge — Reading 5',
         'Academic excellence is often characterized by a student\'s ability to synthesize information from various disciplines into a cohesive and logical argument. This process involves the careful examination of evidence, the identification of significant patterns, and the articulation of original perspectives. Furthermore, the development of critical thinking skills enables individuals to navigate the complexities of modern information systems with confidence. By engaging in rigorous study and continuous self-evaluation, learners can achieve a profound understanding of their chosen fields and contribute meaningfully to the global community.'],

        // WEEK 3 · Session 6 — The Final Evaluation (Advanced)
        [6, 1, 'advanced', 'The Final Evaluation — Reading 1',
         'By the final stage of their journey, the travelers stood in an open landscape where the early light of dawn gradually illuminated the surroundings, revealing distant mountains, scattered vegetation, and expansive skies that extended beyond the visible horizon. They reflected on the entire experience, recalling the sequence of places they had explored, the interactions they had shared, and the transitions they had observed from one environment to another. The journey, marked by movement, observation, and shared experiences, concluded as they prepared to return, carrying with them a continuous narrative of moments that unfolded across time and space. The breeze moved gently across the field, creating subtle motion in the grass while the group remained quietly observing the scene. In that moment, the stillness of the surroundings seemed to capture the end of their journey, as light slowly spread across the land.'],
        [6, 2, 'advanced', 'The Final Evaluation — Reading 2',
         'Reflecting on the progress made over the past few weeks, it is evident that consistent practice leads to a significant transformation in one\'s abilities. The students have successfully navigated through various challenges, moving from basic sounds to complex academic structures with increasing confidence. Each session provided an opportunity to refine their skills and develop a deeper understanding of linguistic accuracy. As they reach this final milestone, they can look back with pride at the dedication they have shown. This experience serves as a foundation for future growth, proving that with focus and effort, any goal can be achieved.'],
        [6, 3, 'advanced', 'The Final Evaluation — Reading 3',
         'The transition from a beginner to a master requires more than just time; it requires a deliberate commitment to excellence and a willingness to learn from every mistake. Throughout this journey, participants have explored the subtle differences in vowel sounds and the rhythmic patterns of natural speech. They have practiced mirroring models and shadowing experts to capture the true flow of the language. Now, as the final evaluation begins, they have the chance to demonstrate their mastery. The clarity of their voices today is a testament to the hard work they have invested since their very first recording.'],
        [6, 4, 'advanced', 'The Final Evaluation — Reading 4',
         'In the quiet moments before the final task, one can sense the collective achievement of the group as they prepare to submit their ultimate performance. The path they have traveled was filled with detailed observations and structured exercises designed to build a professional and authoritative tone. By focusing on accuracy rather than perfection, they have created a supportive environment where progress is celebrated at every stage. This final narrative is not just a reading, but a reflection of the journey they have taken together. As the light of completion shines on their work, they are ready to step forward into the next chapter.'],
        [6, 5, 'advanced', 'The Final Evaluation — Reading 5',
         'The conclusion of a research project often brings a sense of fulfillment and a renewed perspective on the subject of study. For the mentors and participants of A.I.M., this final session represents the culmination of a systematic approach to phonetic integration. They have observed how small adjustments in mouth shape and breathing can lead to a powerful shift in communication. As they record their final baseline comparison, the growth in their accuracy is clear and undeniable. This success is a shared victory, marking the end of a structured path and the beginning of a new level of confidence in their vocal expression.'],
    ];

    // Build list of canonical AIM titles
    $aimTitles = array_column($seeds, 3);

    // Delete old non-AIM seed materials that have no recordings
    // Get IDs to delete first (avoids MySQL subquery-on-same-table restriction)
    $placeholders = implode(',', array_fill(0, count($aimTitles), '?'));
    $toDelete = $pdo->prepare("
        SELECT rm.id FROM reading_materials rm
        LEFT JOIN recordings r ON r.material_id = rm.id
        WHERE rm.title NOT IN ($placeholders)
          AND r.id IS NULL
    ");
    $toDelete->execute($aimTitles);
    $deleteIds = array_column($toDelete->fetchAll(), 'id');
    if (!empty($deleteIds)) {
        $dp = implode(',', array_fill(0, count($deleteIds), '?'));
        $pdo->prepare("DELETE FROM reading_materials WHERE id IN ($dp)")->execute($deleteIds);
    }

    // Upsert each AIM seed: insert if missing by (session_number, reading_number)
    $check  = $pdo->prepare('SELECT id FROM reading_materials WHERE session_number = ? AND reading_number = ?');
    $insert = $pdo->prepare('INSERT INTO reading_materials (session_number, reading_number, level, title, content, created_by) VALUES (?,?,?,?,?,NULL)');
    $update = $pdo->prepare('UPDATE reading_materials SET level=?, title=?, content=? WHERE session_number=? AND reading_number=?');

    foreach ($seeds as [$sn, $rn, $lvl, $ttl, $cnt]) {
        $check->execute([$sn, $rn]);
        if ($check->fetch()) {
            // Update title/content/level in case they changed
            $update->execute([$lvl, $ttl, $cnt, $sn, $rn]);
        } else {
            $insert->execute([$sn, $rn, $lvl, $ttl, $cnt]);
        }
    }

} catch (PDOException $e) {
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}
?>
