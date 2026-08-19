<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
    exit;
}

$sql = "SELECT id FROM users WHERE token = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $token);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid or expired session."]);
    exit;
}

$user_id = $user["id"];

// Record: total cases, wins, losses, draws
$sql = "SELECT
            COUNT(*) AS total_cases,
            SUM(verdict = 'WON') AS wins,
            SUM(verdict = 'LOST') AS losses,
            SUM(verdict = 'DRAW') AS draws
        FROM cases
        WHERE user_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$record = $result->fetch_assoc();

// Argument profile: average of each score across all cases
$sql = "SELECT
            ROUND(AVG(logic_score)) AS avg_logic,
            ROUND(AVG(rebuttal_score)) AS avg_rebuttal,
            ROUND(AVG(evidence_score)) AS avg_evidence,
            ROUND(AVG(persuasion_score)) AS avg_persuasion
        FROM cases
        WHERE user_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$profile = $result->fetch_assoc();

// Last 10 cases' average rebuttal score
$sql = "SELECT AVG(rebuttal_score) AS avg_rebuttal FROM (
            SELECT rebuttal_score FROM cases
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ) AS recent";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$recentAvg = $result->fetch_assoc();

// The 10 cases before those
$sql = "SELECT AVG(rebuttal_score) AS avg_rebuttal FROM (
            SELECT rebuttal_score FROM cases
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10 OFFSET 10
        ) AS previous";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$previousAvg = $result->fetch_assoc();

// Rebuttal improvement trend (needs at least 20 total cases)
$CASES_NEEDED_FOR_TREND = 20;

$rebuttalImprovement = null;
$casesNeededForTrend = 0;

if ($record["total_cases"] < $CASES_NEEDED_FOR_TREND) {
    $casesNeededForTrend = $CASES_NEEDED_FOR_TREND - $record["total_cases"];
}
else if ($previousAvg["avg_rebuttal"] > 0) {
    $rebuttalImprovement = round((($recentAvg["avg_rebuttal"] - $previousAvg["avg_rebuttal"]) / $previousAvg["avg_rebuttal"]) * 100);
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
    "cases_needed_for_trend" => $casesNeededForTrend,
    "recent_cases" => $recentCases
]);
?>