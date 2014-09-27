<?php
date_default_timezone_set('UTC');

$documentStore = [];

$fp = fopen("php://stdin", "r");

function getXmlString($fp)
{
    $xmlString = '';
    while (!feof($fp) && ($line = chop(fgets($fp))) != '</note>') {
        $xmlString .= $line;
    }
    
    if (feof($fp)) {
        throw new Exception('No valid XML found');
    }
    
    return $xmlString . '</note>';
}

function readDocument($fp)
{
    $xmlString = getXmlString($fp);
    $xmlObj = simplexml_load_string($xmlString);
    $json = json_encode($xmlObj);
    $note = json_decode($json, true);
    
    return $note;
}

function createDocument($note)
{
    global $documentStore;
    $documentStore[] = $note;
}

function updateDocument($note)
{
    global $documentStore;
    foreach ($documentStore as &$storedNote) {
        if ($storedNote['guid'] == $note['guid']) {
            $storedNote = $note;
        }
    }
}

function deleteDocument($note)
{
    global $documentStore;
    for ($i=0; $i<count($documentStore); $i++) {
        if ($documentStore[$i]['guid'] == $note['guid']) {
            unset($documentStore[$i]);
            return;
        }
    }
}

function readSearchTerm($fp)
{
    $searchTerm = chop(fgets($fp));
    return $searchTerm;    
}

function match($string, $term)
{
    
    $string = strtolower(trim($string));
    $term = strtolower(trim($term));
    
    if ($string == '') {
        return;
    }
    $wildcardAt = strpos($term, '*');
    
    if ($wildcardAt === false) {
        return $string == $term;
    }
    
    $compareStringPart = substr($string, 0, $wildcardAt);
    $compareTermPart = substr($term, 0, $wildcardAt);
    
    return $compareStringPart == $compareTermPart;
}

function hasTag($note, $term)
{
    foreach ($note['tag'] as $tag) {
        if (match($tag, $term)) {
            return true;
        }
    }
    return false;
}

function createdOnOrAfter($note, $term)
{
    $onOrAfter = strtotime($term);

    $created = strtotime($note['created']);
    if ($created > $onOrAfter) {
        return true;
    }

    return false;
}

function hasKeyword($note, $keyword)
{
    $content = $note['content'];
    
    $content = preg_replace("/[^\w\ _\']+/", '', $content);
    $words = explode(' ', $content);
    
    foreach ($words as $word) {
        if (match($word, $keyword)) {
            return true;
        }
    }
    return false;
}

function search($term)
{
    global $documentStore;
    
    $term = strtolower($term);
    $words = explode(' ', $term);
    
    $found = [];
    
    foreach ($documentStore as $note) {
        $matchesAll = true;
        foreach ($words as $word) {
            if (preg_match('/^tag:(.*)/', $word, $matches)) {
                $func = 'hasTag';
                $word = $matches[1];
            } elseif (preg_match('/^created:(.*)/', $word, $matches)) {
                $func = 'createdOnOrAfter';
                $word = $matches[1];
            } else {
                $func = 'hasKeyword';
            }
                
            if (!$func($note, $word)) {
                $matchesAll = false;
                break;
            }
        }
        if ($matchesAll) {
            $found[] = $note;
        }
    }
    
    usort($found, 'sortByTime');
    return $found;
}

function sortByTime($a, $b)
{
    return strtotime($a['created']) < strtotime($b['created']) ? -1 : 1; 
}

while (!feof($fp)) {
    
    $command = chop(fgets($fp));
    switch ($command) {
        case 'CREATE':
            $note = readDocument($fp);
            createDocument($note);
            break;
        case 'UPDATE':
            $note = readDocument($fp);
            updateDocument($note);
            break;
        case 'DELETE':
            $note = readDocument($fp);
            deleteDocument($note);
            break;   
        case 'SEARCH':
            $term = readSearchTerm($fp);
            $notes = search($term);
            if (count($notes) == 0) {
                echo PHP_EOL;
            } else {
                $output = '';
                foreach ($notes as $note) {
                    $output .= $note['guid'] . ',';
                }
                echo substr($output, 0, strlen($output)-1) . PHP_EOL;
            }
            break;
        default:
            echo 'Error: No valid command found';
            die;
    }
}

function clarity($string)
{
    echo '---------------';
    echo PHP_EOL;
    echo $string;
    echo PHP_EOL;
    echo '---------------';
    echo PHP_EOL;
    
}
