<?php

date_default_timezone_set('UTC');

$stopwords = array("a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount", "an", "and", "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as", "at", "back","be","became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the");

$f_contents = file("wordlist.txt");

$tags = array();
for ($i=0; $i<100; $i++) {
    $tags[] = word();
}

$tags = array("funny", "story", "electronic", "drinks", "breakfast", "lunch", "dinner", "alcohol", "clocks", "london", "computers", "books", "clothing", "retail", "poetry", "music", "shirts", "trousers", "video", "film", "programming", "php", "ruby", "python", "c++", "ninjas", "England", "Japan", "America", "suitcase", "briefcase", "monsters", "windows", "chairman", "company", "money");

for ($i=1; $i<=50; $i++) {
    note($i, "CREATE");
    if ($i % 10 == 0 && $i > 10) {
        note($i - 10, "UPDATE");
    }
    if ($i % 20 == 0 && $i > 20) {
        echo "DELETE" . PHP_EOL;
        echo ($i - 10) . PHP_EOL;
    }
    echo "SEARCH" . PHP_EOL;
    $terms = term();
    while (rand(0,1) == 0) {
        $terms .= ' ' . term();
    }
    echo $terms . PHP_EOL;
}

function utf8_for_xml($string)
{
    return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
}

function word()
{
    global $f_contents;
    return utf8_for_xml(chop($f_contents[array_rand($f_contents)]));
}

function term()
{
    global $tags;
    
    $r = rand(0,3);
    switch ($r) {
        case 0: 
            return 'tag:' . $tags[rand(0, count($tags)-1)];
            break;
        case 1:
            return word();
            break;
        case 2:
            $tag = $tags[rand(0, count($tags)-1)];
            $size = rand(1,strlen($tag));
            $tag = substr($tag, 0, $size) . '*';
            return 'tag:' . $tag;
            break;
        case 3:
            $word = word();
            $size = rand(1,strlen($word));
            $word = substr($word, 0, $size) . '*';
            return $word;
            break;
        case 4:
            $createdTimestamp = time() - rand(0, 60 * 60 * 24 * 365);
            $createdDatestring = date('%Y%m%d', $createdTimestamp);
            return 'created:' . $createdDatestring;
            break;
    }
}

function note($id, $command)
{
    global $stopwords, $tags;
    echo $command . PHP_EOL;
    $numWords = rand(1,250);
    $content = '';
    for ($j=0; $j<$numWords; $j++) {
        $content .= ' ' . $stopwords[rand(0, count($stopwords)-1)];
        $content .= ' ' . word();
        if (rand(0,50) == 0) {
            $content .= PHP_EOL;
        }
    }
    
    $createdTimestamp = time() - rand(0, 60 * 60 * 24 * 365);
    $createdDatestring = date(DateTime::ISO8601, $createdTimestamp);
    echo "  <note>" . PHP_EOL;
    echo "  <guid>$id</guid>" . PHP_EOL;
    echo "  <created>$createdDatestring</created>" . PHP_EOL;
    
    $numTags = rand(0, 5);
    for ($j=0; $j<$numTags; $j++) {
    $tag = $tags[rand(0, count($tags)-1)];
    echo "  <tag>$tag</tag>" . PHP_EOL;
    }
    
    echo "  <content>" . PHP_EOL . $content . PHP_EOL . "</content>" . PHP_EOL;
    echo "</note>" . PHP_EOL;
}
