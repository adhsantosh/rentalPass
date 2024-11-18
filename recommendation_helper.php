<?php
// recommendation_helper.php

function getBicycleSimilarityMatrix($conn) {
    // Fetch all rental data
    $rentals = $conn->query("SELECT UID, VID FROM rentals");
    
    $userBikes = [];
    while ($row = $rentals->fetch_assoc()) {
        $userBikes[$row['UID']][] = $row['VID'];
    }

    $similarityMatrix = [];
    $bicycleCounts = [];

    foreach ($userBikes as $bicycles) {
        foreach ($bicycles as $b1) {
            if (!isset($bicycleCounts[$b1])) $bicycleCounts[$b1] = 0;
            $bicycleCounts[$b1]++;
            foreach ($bicycles as $b2) {
                if ($b1 == $b2) continue;
                if (!isset($similarityMatrix[$b1][$b2])) $similarityMatrix[$b1][$b2] = 0;
                $similarityMatrix[$b1][$b2]++;
            }
        }
    }

    foreach ($similarityMatrix as $b1 => $related) {
        foreach ($related as $b2 => $count) {
            $similarityMatrix[$b1][$b2] = $count / sqrt($bicycleCounts[$b1] * $bicycleCounts[$b2]);
        }
    }
    return $similarityMatrix;
}

function getRecommendedBicycles($conn, $userId, $similarityMatrix, $numRecommendations = 5) {
    $stmt = $conn->prepare("SELECT VID FROM rentals WHERE UID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $userBikes = [];
    while ($row = $result->fetch_assoc()) {
        $userBikes[] = $row['VID'];
    }

    $recommendations = [];
    foreach ($userBikes as $bicycle) {
        if (!isset($similarityMatrix[$bicycle])) continue;
        foreach ($similarityMatrix[$bicycle] as $relatedBike => $similarity) {
            if (in_array($relatedBike, $userBikes)) continue;
            if (!isset($recommendations[$relatedBike])) $recommendations[$relatedBike] = 0;
            $recommendations[$relatedBike] += $similarity;
        }
    }

    arsort($recommendations);
    return array_slice(array_keys($recommendations), 0, $numRecommendations);
}
?>