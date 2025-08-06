<?php
function getSimilarityMatrix($conn, $columnName) {
    $query = "SELECT UID, $columnName FROM rentals WHERE $columnName IS NOT NULL";
    $rentals = $conn->query($query);
    
    $userItems = [];
    while ($row = $rentals->fetch_assoc()) {
        $userItems[$row['UID']][] = $row[$columnName];
    }

    $similarityMatrix = [];
    $itemCounts = [];

    foreach ($userItems as $items) {
        foreach ($items as $i1) {
            if (!isset($itemCounts[$i1])) $itemCounts[$i1] = 0;
            $itemCounts[$i1]++;
            foreach ($items as $i2) {
                if ($i1 == $i2) continue;
                if (!isset($similarityMatrix[$i1][$i2])) $similarityMatrix[$i1][$i2] = 0;
                $similarityMatrix[$i1][$i2]++;
            }
        }
    }

    foreach ($similarityMatrix as $i1 => $related) {
        foreach ($related as $i2 => $count) {
            $similarityMatrix[$i1][$i2] = $count / sqrt($itemCounts[$i1] * $itemCounts[$i2]);
        }
    }

    return $similarityMatrix;
}

function getRecommendations($conn, $userId, $similarityMatrix, $columnName, $limit = 5) {
    $stmt = $conn->prepare("SELECT $columnName FROM rentals WHERE UID = ? AND $columnName IS NOT NULL");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $userItems = [];
    while ($row = $result->fetch_assoc()) {
        $userItems[] = $row[$columnName];
    }

    $recommendations = [];
    foreach ($userItems as $item) {
        if (!isset($similarityMatrix[$item])) continue;
        foreach ($similarityMatrix[$item] as $relatedItem => $score) {
            if (in_array($relatedItem, $userItems)) continue;
            if (!isset($recommendations[$relatedItem])) $recommendations[$relatedItem] = 0;
            $recommendations[$relatedItem] += $score;
        }
    }

    arsort($recommendations);
    return array_slice(array_keys($recommendations), 0, $limit);
}
?>
