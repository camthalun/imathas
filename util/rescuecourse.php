<?php
require("../validate.php");
if ($myrights<100) {
	exit;
}
if (isset($_REQUEST['cid'])) {
	$query = "SELECT itemorder,blockcnt FROM imas_courses WHERE id='{$_REQUEST['cid']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$items = unserialize(mysql_result($result,0,0));
	$blockcnt = mysql_result($result,0,1);
} else {
	exit;
}

$allitems = array();
$query = "SELECT id FROM imas_items WHERE courseid='{$_REQUEST['cid']}'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$allitems[] = $row[0];
}

$itemsfnd = array();
function fixsub(&$items) {
	global $allitems,$itemsfnd;
	foreach($items as $k=>$item) {
		if ($item==null) {
			unset($items[$k]);
		} else if (is_array($item)) {
			if (!isset($item['items']) || !is_array($item['items'])) {
				unset($items[$k]);
			} else if (count($item['items'])>0) {
				fixsub($items[$k]['items']);
			}
		} else {
			if ($item==null || $item=='') {
				unset($items[$k]);
			} else if (!in_array($item,$allitems)) {
				unset($items[$k]);
				echo "Removed unused item from itemorder<br/>";
			} else {
				$itemsfnd[] = $item;
			}
		}
	}
	$items = array_values($items);
}
fixsub($items);

$recovereditems = array();

foreach ($allitems as $item) {
	if (!in_array($item,$itemsfnd)) {
		$recovereditems[] = $item;
	}
}

if (count($recovereditems)>0) {
	$block = array();
	$block['name'] = "Recovered items";
	$block['id'] = $blockcnt;
	$block['startdate'] = 0;
	$block['enddate'] = 2000000000;
	$block['avail'] = 0;
	$block['SH'] = "HO";
	$block['colors'] = '';
	$block['fixedheight'] = 0;
	$block['public'] = 0;
	$block['items'] = $recovereditems;
	array_push($items,$block);
	echo "recovered ". count($recovereditems) . "items";
	print_r($items);
	$itemorder = addslashes(serialize($items));
	$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt=blockcnt+1 WHERE id='{$_REQUEST['cid']}'";
	mysql_query($query) or die("Query failed : $query" . mysql_error());
} else {
	$itemorder = addslashes(serialize($items));
	$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='{$_REQUEST['cid']}'";
	mysql_query($query) or die("Query failed : $query" . mysql_error());
}

echo "Done";



//print_r($items);


?>
