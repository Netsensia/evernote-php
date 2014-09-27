<?php

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

function hasTag($tag)
{
    return true;
}

function createdOnOrAfter($date)
{
    return true;
}

function hasKeyword($keyword)
{
    return true;    
}

function search($term)
{
    global $documentStore;
    
    if (preg_match('/^tag:(.*)/', $term, $matches)) {
        $func = 'hasTag';
        $term = $matches[1];
    } elseif (preg_match('/^created:(.*)/', $term, $matches)) {
        $func = 'createdOnOrAfter';
        $term = $matches[1];
    } else {
        $func = 'hasKeyword';
    }

    $found = [];
        
    foreach ($documentStore as $note) {
        if ($func($term)) {
            $found[] = $note['guid'];
        }   
    }
    
    return $found;
}

while (!feof($fp)) {
    
    $command = chop(fgets($fp));
    switch ($command) {
        case 'CREATE':
        case 'UPDATE':
        case 'DELETE':
            $note = readDocument($fp);
        case 'CREATE':
            createDocument($note);
            break;
        case 'UPDATE':
            updateDocument($note);
            break;
        case 'DELETE':
            deleteDocument($note);
            break;   
        case 'SEARCH':
            $term = readSearchTerm($fp);
            $guids = search($term);
            if (count($guids) == 0) {
                echo PHP_EOL;
            } else {
                $output = '';
                foreach ($guids as $guid) {
                    $output .= $guid . ',';
                }
                echo substr($output, 0, strlen($output)-1) . PHP_EOL;
            }
            break;
        default:
            echo 'Error: No valid command found';
            die;
    }
}