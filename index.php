<!DOCTYPE html>
<html lang="en">
	<head>
                <title>HIGE</title>

		<!-- ANGULARJS -->
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.6/angular.min.js"></script>
		
		<!--JQuery-->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

		<!-- Custom stylesheet -->
		<link rel="stylesheet" href="style/style.css">
		
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		
		<!-- ENABLE BOOTSTRAP -->
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		
		<!-- Font-Awesome ICONS -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	
	</head>
	<body ng-app="HIGE-app">
		<!--HEADER-->
		<div class="container-fluid">
			<div class="page-header">
				<div class="row">
				<div class="col-md-4">
				<center>
					<p><img src="images/WMU.png" class="logo" /></p>
					<p class="title">Haenicke Institute for Global Education</p>
				</center>
				</div>
				</div>
			</div>
		</div>
		<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
			<!--DOCS SUBMIT FORM BUTTONS-->
			<div class="row">
				<form enctype="multipart/form-data" ng-controller="uploadCtrl" id="upF" target="#">
				<center>
					<table>
						<tr>
							<td><p class="lt">SUBMIT FORM:<input type="file" file-model="fD" name="fD" id="fD" required accept="application/pdf" /></p></td>
						</tr>
						<tr>
							<td><p class="lt">SUPPORTING DOCS:<input type="file" file-model="sD" name="sD" id="sD" accept="application/pdf" /></p></td>
						</tr>
						<tr>
							<td><center><button class="styled-button-3" id="sub" style="background-color: green !important; border-color: green !important; margin-top: 10px;" ng-click="uploadFile()">SUBMIT</button></center></td>
						</tr>
						<?php
							if(isset($_GET["status"]))
							{
								if($_GET["status"] == "success")
								{
									echo '<tr>
										<td><center><span class="lt" style="color: green; font-size: 22px;" id="smsg"><b>File(s) Uploaded.</b></span></center></td>
									</tr>';
								}
							}
						?>
						<tr>
							<td><center><span id="loadSpinner" class="lt" style="visibility: hidden;">Submitting... <i class="fa fa-spinner fa-spin" style="font-size:35px !important;"></i></span></center></td>
						</tr>
						<tr><td><center><p class="title"><a href="https://wmich.edu/sites/default/files/attachments/u820/2017/IEFDF%20Proposal%20Form.pdf" target="_blank">Download Application Form</a></p></center></td></tr>
					</table>
				</center>
				</form>
			</div>
			<!--DOCS FORM-->
			<div class="row">
			<center>
				<p class="title" style="font-size: 24px !important; margin-top: 25px; margin-bottom:25px;">How to Apply:</p>
			</center>
			</div>
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<p class="title">1. Download and fill out the Application Form</p>
					<p class="title">2. Upload the completed Application Form using the 'SUBMIT FORM' button</p>
					<p class="title">3. Upload any supporting documents using the 'SUPPORTING DOCS' button</p>
					<p class="title">4. You will notified of your application status via e-mail and you can always check it here in the 'APPLICATION STATUS' section</p>
				</div>
			</div>
		</div>
		<!--BODY-->
	
	</body>
	
	<!-- AngularJS Script -->
	<script>
		
		var myApp = angular.module('HIGE-app', []);

		myApp.directive('fileModel', ['$parse', function ($parse) {
			return {
			restrict: 'A',
			link: function(scope, element, attrs) {
				var model = $parse(attrs.fileModel);
				var modelSetter = model.assign;

				element.bind('change', function(){
					scope.$apply(function(){
						modelSetter(scope, element[0].files[0]);
					});
				});
			}
		   };
		}]);

		// We can write our own fileUpload service to reuse it in the controller
		myApp.service('fileUpload', ['$http', function ($http) {
			this.uploadFileToUrl = function(file, uploadUrl){
				$("#loadSpinner").css('visibility', 'visible');
				$("#sub").prop("disabled", true);				
				 var fd = new FormData();
				 fd.append('file', file);
				 $http.post(uploadUrl, fd, {
					 transformRequest: angular.identity,
					 headers: {'Content-Type': undefined,'Process-Data': false}
				 })
				 .then(function(){
					console.log("Success");
					$("#sub").prop("disabled", false);
					$("#loadSpinner").css('visibility', 'hidden');
				 },
				 function(){
					 console.log("Failure");
					$("#sub").prop("disabled", false);
					$("#loadSpinner").css('visibility', 'hidden');
				 });
			 }
		 }]);

		myApp.controller('uploadCtrl', ['$window', '$scope', 'fileUpload', function($window, $scope, fileUpload){

		   $scope.uploadFile = function(){
				if($("#fD").val() != "")
				{
					$("#loadSpinner").css('visibility', 'visible');
					var file = $scope.fD;
					console.log('file is ' );
					console.dir(file);

					var uploadUrl = "upload.php";
					fileUpload.uploadFileToUrl(file, uploadUrl);
					if($("#sD").val() != "")
					{
						var fileS = $scope.sD;
						console.log('Supporting file is ' );
						console.dir(fileS);
						fileUpload.uploadFileToUrl(fileS, uploadUrl);
					}
					$window.location.href = 'index.php?status=success';
				}
		   };

		}]);
		
		var c = 6;
		setInterval(function() {
			if(c != 0)
				c--;
			if(c == 0)
				if(document.getElementById("smsg") != null)
					$("#smsg").remove();
		}, 1000);
	</script>
	<!-- End Script -->
</html>