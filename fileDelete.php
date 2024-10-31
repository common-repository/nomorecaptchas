<?php
$selectedFiles = $_POST ['select_files'];

foreach ( $_POST ['select_files'] as $filename ) {
	// To prevent traversal attacks, you need to validate $filename
	// For example, if it would only be expected to be alphanumeric:
	echo $filename;
	if (! unlink ( $filename )) {
		echo "Error deleting $filename";
	} else {
		echo "File Deleted Successfully";
	}
}
$_POST ['select_files'] = "";
?>