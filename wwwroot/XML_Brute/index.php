<?php

	$config = parse_ini_file("config.ini.php");

?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
				<title>XML Brute - XML to Relational Database Converter</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<!-- Bootstrap -->
		<link href="css/bootstrap.min.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<div class="row">
				<h1 class="h1">XML Brute: XML to Relational Database Converter</h1>
			</div>
			<form method="POST" action="XML_Brute.php" accept-charset="UTF-8" role="form" class="form-horizontal col-sm-12" enctype="multipart/form-data">
				<div class="form-group col-sm-12">
					<label for="target_file" class="col-sm-2 control-label required">XML file to convert:</label>
					<div class="col-sm-2">
						<input name="MAX_FILE_SIZE" type="hidden" value="1073741824">					
						<input name="target_file" type="file" id="target_file">
					</div>
					<div class="form-group col-sm-12">
						<label for="export_format" class="col-sm-2 control-label required">Convert to:</label>
						<div class='col-sm-4'>
							<select class="form-control" autofocus="autofocus" id="export_format" required="required" name="export_format">
<?php
foreach ($config["DbFormats"] as $key=>$label){
	$selected = ($key == $config["DbFormatsDefault"]) ? " selected=\"selected\"" : "";
	echo "\t\t\t\t\t\t\t\t<option value=\"".$key."\"".$selected.">".$label."</option>\r\n";
}
?>
							</select>
						</div>
					</div>
				</div>
				<div class='form-group col-sm-4'>
					<input class="form-control btn btn-default" id="save_button" type="submit" value="Upload XML file for conversion">
				</div>
			</form>	
			<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
			<script src="js/jquery.min.js"></script>
			<!-- Include all compiled plugins (below), or include individual files as needed -->
			<script src="js/bootstrap.min.js"></script>
		</div>
	</body>
</html>
