<?php
//Set the content-type header and charset.
header("Content-Type: text/css; charset=utf-8");

include "utility.php"; // Might be nice to get $type one, but not sure we can do it in this context

// Get list of corpora, regardless of whether they have categories
$result = $conn = openConnection (false)->query("SELECT id FROM corpus ORDER BY corpus.id");
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
  echo ".category{$row['id']}, ";
}

// Provide lookup CSS
echo ".categoryend {
            border: 2px solid #CCCCCC;
            border-radius: 8px 8px 8px 8px;
            font-size: 24px;
            height: 45px;
            line-height: 30px;
            outline: medium none;
            padding: 8px 12px;
            width: 400px;
        }
"
?>
