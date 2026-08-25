<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
    return;
}

$sql = "SELECT id FROM users WHERE token = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $token);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid or expired session."]);
    return;
}

$user_id = $user["id"];

// Record: total cases, wins, losses, draws (COALESCE so a 0-case user gets 0s, not NULLs)
$sql = "SELECT
            COUNT(*) AS total_cases,
            COALESCE(SUM(verdict = 'WON'), 0) AS wins,
            COALESCE(SUM(verdict = 'LOST'), 0) AS losses,
            COALESCE(SUM(verdict = 'DRAW'), 0) AS draws
        FROM cases
        WHERE user_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$record = $result->fetch_assoc();

// Argument profile: average of each score across all cases
$sql = "SELECT
            COALESCE(ROUND(AVG(logic_score)), 0) AS avg_logic,
            COALESCE(ROUND(AVG(rebuttal_score)), 0) AS avg_rebuttal,
            COALESCE(ROUND(AVG(evidence_score)), 0) AS avg_evidence,
            COALESCE(ROUND(AVG(persuasion_score)), 0) AS avg_persuasion
        FROM cases
        WHERE user_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$profile = $result->fetch_assoc();

// Most recent case's rebuttal score
$sql = "SELECT rebuttal_score FROM cases WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$latestCase = $result->fetch_assoc();

// Rebuttal trend: compares the newest case's rebuttal score against the average
// of every case before it, so it works from the 2nd case onward (no fixed case count needed)
$rebuttalImprovement = null;
$latestRebuttalScore = $latestCase ? (int) $latestCase["rebuttal_score"] : null;

if ($record["total_cases"] > 1) {
    $sql = "SELECT SUM(rebuttal_score) AS sum_rebuttal FROM cases WHERE user_id = ?";
    $query = $mysql->prepare($sql);
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $sumRow = $result->fetch_assoc();

    $priorAvg = ($sumRow["sum_rebuttal"] - $latestRebuttalScore) / ($record["total_cases"] - 1);

    if ($priorAvg > 0) {
        $rebuttalImprovement = round((($latestRebuttalScore - $priorAvg) / $priorAvg) * 100);
    }
}

// Recent cases list (last 5)
$sql = "SELECT id, topic, user_stance, verdict, score
        FROM cases
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$recentCases = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "success" => true,
    "record" => $record,
    "argument_profile" => $profile,
    "rebuttal_improvement" => $rebuttalImprovement,
    "latest_rebuttal_score" => $latestRebuttalScore,
    "recent_cases" => $recentCases
]);
?>