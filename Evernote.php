<?php
date_default_timezone_set('UTC');

class Evernote 
{
    private $documentStore = [];
    
    function getDocumentCount()
    {
        return count($this->documentStore);
    }
    
    function getDocument($documentNumber)
    {
        if (isset($this->documentStore[$documentNumber])) {
            return $this->documentStore[$documentNumber];
        } else {
            return null;
        }
    }
    
    function getXmlString($fp)
    {
        $xmlString = '';
        while (!feof($fp) && ($line = chop(fgets($fp))) != '</note>') {
            $xmlString .= $line;
        }
        
        if (feof($fp)) {
            throw new Exception('No valid XML found');
        }
        
        $xmlString = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $xmlString);
        
        return trim($xmlString) . '</note>';
    }
    
    function makeNoteFromXml($xmlString)
    {
        $xmlObj = simplexml_load_string($xmlString);
        $json = json_encode($xmlObj);
        $note = json_decode($json, true);
        
        return $note;    
    }
    
    function readNote($fp)
    {
        $xmlString = $this->getXmlString($fp);
        return $this->makeNoteFromXml($xmlString);
    }
    
    function createDocument( $note)
    {
        $this->documentStore[] = $note;
    }
    
    function updateDocument($note)
    {
        foreach ($this->documentStore as &$storedNote) {
            if ($storedNote['guid'] == $note['guid']) {
                $storedNote = $note;
            }
        }
    }
    
    function deleteDocument( $guid)
    {
        for ($i=0; $i<count($this->documentStore); $i++) {
            if ($this->documentStore[$i]['guid'] == $guid) {
                array_splice($this->documentStore, $i);
                return;
            }
        }
    }
    
    function readNextLine($fp)
    {
        $line = chop(fgets($fp));
        return $line;    
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
        if (isset($note['tag'])) {
            if (is_array($note['tag'])) {
                foreach ($note['tag'] as $tag) {
                    if ($this->match($tag, $term)) {
                        return true;
                    }
                }
                return false;
            } else {
                if ($this->match($note['tag'], $term)) {
                    return true;
                }
                return false;
            }
        }
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
        
        $content = preg_replace("/[^\w\ _\'\-]+/", '', $content);
        $words = explode(' ', $content);
        
        foreach ($words as $word) {
            if ($this->match($word, $keyword)) {
                return true;
            }
        }
        return false;
    }
    
    function search($term)
    {   
        $term = strtolower($term);
        $words = explode(' ', $term);
        
        $found = [];
        
        foreach ($this->documentStore as $note) {
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
                    
                if (!$this->$func($note, $word)) {
                    $matchesAll = false;
                    break;
                }
            }
            if ($matchesAll) {
                $found[] = $note;
            }
        }
        
        usort($found, 'Evernote::sortByTime');
        return $found;
    }
    
    static function sortByTime($a, $b)
    {
        return strtotime($a['created']) < strtotime($b['created']) ? -1 : 1; 
    }
    
    function go()
    {
        $fp = fopen("php://stdin", "r");
        
        while (!feof($fp)) {
            
            $command = chop(fgets($fp));
            switch ($command) {
                case 'CREATE':
                    $note = $this->readNote($fp);
                    $this->createDocument($note);
                    break;
                case 'UPDATE':
                    $note = $this->readNote($fp);
                    $this->updateDocument($note);
                    break;
                case 'DELETE':
                    $guid = $this->readNextLine($fp);
                    $this->deleteDocument($guid);
                    break;   
                case 'SEARCH':
                    $term = $this->readNextLine($fp);
                    $notes = $this->search($term);
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
    }
}



