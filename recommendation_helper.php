<?php
function getDatabaseConnection() {
    $pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// ------------------ Cosine similarity ------------------
function cosineSimilarity($vec1, $vec2) {
    $dot = 0; $normA = 0; $normB = 0;
    foreach ($vec1 as $k => $v) { $dot += $v * ($vec2[$k] ?? 0); $normA += $v*$v; }
    foreach ($vec2 as $v) { $normB += $v*$v; }
    return ($normA && $normB) ? $dot / (sqrt($normA)*sqrt($normB)) : 0;
}

// ------------------ Compute item-item similarity ------------------
function computeItemSimilarity($matrix) {
    $itemUsers = [];
    foreach ($matrix as $user => $items) {
        foreach ($items as $item => $score) {
            $itemUsers[$item][$user] = $score;
        }
    }

    $sim = [];
    foreach ($itemUsers as $a => $usersA) {
        foreach ($itemUsers as $b => $usersB) {
            if ($a == $b) continue;
            $sim[$a][$b] = cosineSimilarity($usersA, $usersB);
        }
    }
    return $sim;
}

// ------------------ Feature similarity ------------------
function featureSim($v1, $v2) {
    if ($v1['type'] !== $v2['type']) return 0; // cross-type 0
    $brandScore = ($v1['brand'] === $v2['brand']) ? 1 : 0;
    $priceScore = max(0, 1 - abs($v1['price'] - $v2['price'])/1000);
    return 0.6*$brandScore + 0.4*$priceScore;
}

// ------------------ Hybrid similarity (type-safe) ------------------
function hybridSim($collab, $feature, $vehicles, $alpha=0.7) {
    $hybrid = [];
    foreach ($collab as $a => $simItems) {
        foreach ($simItems as $b => $score) {
            if (!isset($vehicles[$a]) || !isset($vehicles[$b])) continue;
            if ($vehicles[$a]['type'] !== $vehicles[$b]['type']) continue;
            $featureScore = $feature[$a][$b] ?? 0;
            $hybrid[$a][$b] = $alpha*$score + (1-$alpha)*$featureScore;
        }
    }
    return $hybrid;
}

// ------------------ Recommend function ------------------
function recommend($vehicleId, $simMatrix, $vehicles, $topN=3) {
    if (!isset($simMatrix[$vehicleId])) return [];
    arsort($simMatrix[$vehicleId]);
    $top = array_slice($simMatrix[$vehicleId], 0, $topN, true);
    $res = [];
    foreach ($top as $id => $score) {
        if (!isset($vehicles[$id])) continue;
        $res[] = array_merge(['id'=>$id, 'score'=>$score], $vehicles[$id]);
    }
    return $res;
}

// ------------------ Prepare matrices ------------------
function prepareMatrices() {
    $pdo = getDatabaseConnection();

    // Two-wheeler interactions
    $sqlTW = "SELECT UID, TWID FROM rentals WHERE TWID IS NOT NULL";
    $resultTW = $pdo->query($sqlTW);
    $twMatrix = [];
    foreach ($resultTW as $row) {
        $twMatrix[$row['UID']][$row['TWID']] = 1;
    }

    // Four-wheeler interactions
    $sqlFW = "SELECT UID, FWID FROM rentals WHERE FWID IS NOT NULL";
    $resultFW = $pdo->query($sqlFW);
    $fwMatrix = [];
    foreach ($resultFW as $row) {
        $fwMatrix[$row['UID']][$row['FWID']] = 1;
    }

    // Vehicle metadata
    $vehicles = [];
    $sql = "SELECT TWID as id, name as brand, price, photo, 'two_wheeler' as type FROM two_wheeler
            UNION
            SELECT FWID as id, name as brand, price, photo, 'four_wheeler' as type FROM four_wheeler";
    $res = $pdo->query($sql);
    foreach ($res as $v) {
        $vehicles[$v['id']] = ['brand'=>$v['brand'], 'price'=>floatval($v['price']), 'photo'=>$v['photo'], 'type'=>$v['type']];
    }

    // Compute similarities
    $twCF = computeItemSimilarity($twMatrix);
    $fwCF = computeItemSimilarity($fwMatrix);

    // Feature similarity
    $featureMatrix = [];
    foreach ($vehicles as $idA => $vA) {
        foreach ($vehicles as $idB => $vB) {
            if ($idA == $idB) continue;
            $featureMatrix[$idA][$idB] = featureSim($vA, $vB);
        }
    }

    // Hybrid
    $twHybrid = hybridSim($twCF, $featureMatrix, $vehicles);
    $fwHybrid = hybridSim($fwCF, $featureMatrix, $vehicles);

    return [$twHybrid, $fwHybrid, $vehicles, $twCF, $fwCF];
}
?>
