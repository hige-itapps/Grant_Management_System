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
		<?php
			include 'include/header.php';
		?>
		<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
			<form enctype="multipart/form-data" class="form-horizontal" id="submitApp" name="submitApp" method="POST" action="db.php">
				<div ng-controller="budget">
					<!--APPLICANT INFO-->
					<div class="row">
						<center>
						<p class="title">Applicant Information:</p>
						</center>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
					<!--NAME-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputName"><p class="title" style="font-size: 18px !important;">Name:</p></label>
								<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" required />
							</div>
						</div>
					<!--EMAIL-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputEmail"><p class="title" style="font-size: 18px !important;">Email Address:</p></label>
								<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email" required />
							</div>
						</div>
					<!--DEPARTMENT-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputDept"><p class="title" style="font-size: 18px !important;">Department:</p></label>
								<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Department" required />
							</div>
						</div>
					<!--DEPT MAIL STOP-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputDeptM"><p class="title" style="font-size: 18px !important;">Department Mail Stop:</p></label>
								<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Department Mail Stop" maxlength="4" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
					<!--TRAVEL DATES-->
						<div class="col-md-1">
							<div class="form-group">
								<label for="inputFrom"><p class="title" style="font-size: 18px !important;">Travel From:</p></label>
								<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" required />
							</div>
						</div>
						<div class="col-md-1">
							<div class="form-group">
								<label for="inputFrom"><p class="title" style="font-size: 18px !important;">To:</p></label>
								<input type="date" class="form-control" id="inputTTo" name="inputTTo" onchange="TDate()" required />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<br><br>
					<!--RESEARCH INFO-->
					<div class="row">
						<center>
						<p class="title">Research Information:</p>
						</center>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
					<!--TITLE-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputRName"><p class="title" style="font-size: 18px !important;">Title of Research:</p></label>
								<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title" required />
							</div>
						</div>
					<!--ACTIVITY DATES-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputFrom"><p class="title" style="font-size: 18px !important;">Activity Date From:</p></label>
								<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" onchange="ADateF()"  required />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputTo"><p class="title" style="font-size: 18px !important;">Activity Date To:</p></label>
								<input type="date" class="form-control" id="inputATo" name="inputATo" onchange="ADateT()"  required />
							</div>
						</div>
					<!--DESTINATION-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputDest"><p class="title" style="font-size: 18px !important;">Destination:</p></label>
								<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" required />
							</div>
						</div>
					<!--AMOUNT REQ-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputAR"><p class="title" style="font-size: 18px !important;">Amount Requested:</p></label>
								<input type="text" class="form-control" id="inputAR" name="inputAR" placeholder="Amount Requested" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
					<!--PURPOSE-->
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="form-group">
								<label for="inputAR<p class="title" style="font-size: 18px !important;">Purpose of Travel:</p>
							</div>
						</div>
						
					</div><div class="row"><div class="col-md-1"></div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr1" type="checkbox" value="Research">Research</label></p>
							</div>
						</div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr2" type="checkbox" value="Conference">Conference</label></p>
							</div>
						</div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr3" type="checkbox" value="Creative Activity">Creative Activity</label></p>
							</div>
						</div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr4dummy" id="pr4dummy" type="checkbox" value="">Other, explain.</label></p>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<input type="text" class="form-control" id="pr4" name="pr4" disabled="true" placeholder="Other, explain..." />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row"><div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="form-group">
								<input type="text" class="form-control" id="eS" name="eS" placeholder="Are you receiving other funding? Who is providing the funds? How much?" />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
					<div class="col-md-1"></div>
						<div class="col-md-10">
						<center>
							<p class="title" style="font-size: 14px !important;">Please ensure to include supporting documentation: Conference letter of acceptance; invitation letter for research or creative activity, etc. in 1 PDF, using the upload button below.</p> 
						</center>
						</div>
					<div class="col-md-1"></div>
					</div>
					<div class="row">
					<!--PROPOSAL SUMMARY-->
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="form-group">
								<textarea class="form-control" id="props" name="props" placeholder="Proposal summary, MAX 150 words" rows=10 required /></textarea>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row"><div class="col-md-1"></div>
						<p class="title" style="font-size: 18px !important;">Please indicate which of the prioritized goals of the IEFDF this proposal fulfills:</p>
					<div class="col-md-1"></div></div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg1" type="checkbox" value="">
								Support for international collaborative research and creative activities, or for international research, including archival and field work.</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg2" type="checkbox" value="">
								Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg3" type="checkbox" value="">
								Support for attendance at international conferences, seminars or workshops.</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg4" type="checkbox" value="">
								Support for scholarly international travel in order to enrich international knowledge, which will directly
								contribute to the internationalization of the WMU curricula.</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row"><div class="col-md-1"></div>
						<p class="title" style="font-size: 18px !important;">Budget:(please separate room and board calculating per diem)</p>
					<div class="col-md-1"></div></div>
					<div class="row"><div class="col-md-3"></div>
						<div class="col-md-2">
							<p class="title" style="font-size: 16px !important;">Expense:</p>
						</div>
						<div class="col-md-2">
							<p class="title" style="font-size: 16px !important;">Comments:</p>
						</div>
						<div class="col-md-2">
							<p class="title" style="font-size: 16px !important;">Amount:</p>
						</div>
						<div class="col-md-3"></div>
					</div>
					<div class="row" ng-repeat="bitem in bitems">
						<div class="col-md-3"></div>
						<div class="col-md-2">
							<div class="form-group">
								<select class="form-control" name="{{bitem.ex}}" required />
									<option value="Air Travel">Air Travel</option>
									<option value="Ground Travel">Ground Travel</option>
									<option value="Hotel">Hotel</option>
									<option value="Other">Other</option>
								</select>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<input type="text" class="form-control" name="{{bitem.com}}" placeholder="Explain..." required />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<input type="text" class="form-control" name="{{bitem.am}}" ng-model="bitem.val" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
						<div class="col-md-3"></div>
					</div>
					<div class="row">
						<div class="col-md-5"></div>
						<div class="col-md-2">
							<i class="fa fa-plus-circle fa-2x" style="color: blue !important;" aria-hidden="true" ng-click="addInput()"></i>
							<i class="fa fa-minus-circle fa-2x" style="color: red !important;" aria-hidden="true" ng-click="remInput()"></i>
						</div>
						<div class="col-md-5"></div>
					</div>
					<div class="row"><div class="col-md-5"></div>
						<div class="col-md-1">
							<p class="title" style="font-size: 16px !important;">Total:</p>
						</div>
						<div class="col-md-1">
							<p class="title" style="font-size: 16px !important;">${{ getTotal() }}</p>
						</div>
						<div class="col-md-5"></div>
					</div>
					<br><br>
					<!--UPLOAD DOCS FORM-->
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-5">
								<label for="fD"><p class="title" style="font-size: 18px !important;">PROPOSAL NARRATIVE:</p></label><input type="file" name="fD" id="fD" required accept="application/pdf" />
						</div>
						<div class="col-md-5">
							<label for="sD"><p class="title" style="font-size: 18px !important;">SUPPORTING DOCUMENTS:</p></label><input type="file" name="sD" id="sD" accept="application/pdf" />
						</div>
						<div class="col-md-1"></div>
					</div>
					<br><br>
					<!--APPLICANT SIGNATURE-->
					<div class="row">
						<center>
						<p class="title">Applicant E-Signature:</p>
						</center>
					</div>
					<div class="row">
						<div class="col-md-4"></div>
					<!--NAME-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputName"><p class="title" style="font-size: 18px !important;">Name:</p></label>
								<input type="text" class="form-control" id="inputASig" name="inputASig" placeholder="Enter Name" required />
							</div>
						</div>
						<div class="col-md-4"></div>
					</div>
					<div class="row">
						<div class="col-md-5"></div>
						<div class="col-md-2">
							<input type="submit" class="styled-button-3" id="sub" name="sub" style="background-color: green !important; border-color: green !important; margin-top: 10px;" value="SUBMIT APPLICATION" />
						</div>
						<div class="col-md-5"></div>
					</div>
				</div>
				<center>
					<?php
						if(isset($_GET["status"]))
						{
							if($_GET["status"] == "success")
							{
								echo '<br><span class="lt" style="color: green; font-size: 22px;" id="smsg"><b>Application Submitted.</b></span>';
							}
						}
					?>
					<span id="loadSpinner" class="lt" style="visibility: hidden;">Submitting... <i class="fa fa-spinner fa-spin" style="font-size:35px !important;"></i></span>
				</center>
				<br><br>
			</form>
		<!--BODY-->
	
	</body>
	
	<!-- AngularJS Script -->
	<script>
		
		var myApp = angular.module('HIGE-app', []);

		/*myApp.directive('fileModel', ['$parse', function ($parse) {
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
					$window.location.href = 'application_builder.php?status=success';
				}
		   };

		}]);*/

		
		myApp.controller('budget', ['$scope', function($scope){
			$scope.bitems = [];
			$scope.addInput = function() {
				expenses = 'expense' + ($scope.bitems.length + 1);
				com = 'comm' + ($scope.bitems.length + 1);
				amounts = 'amount' + ($scope.bitems.length + 1);    
				$scope.bitems.push({
					ex: expenses,
					com: com,
					am: amounts,
					val: 0
				})       
			}
			$scope.remInput = function() {
				$scope.bitems.splice($scope.bitems.length - 1, 1);
			}
			$scope.getTotal = function(){
				var total = 0;
				for(var i = 0; i < $scope.bitems.length; i++){
					total += parseFloat($scope.bitems[i]["val"]);
				}
				return (total).toFixed(2);
			}
		}]);
		
		var c = 6;
		setInterval(function() {
			if(c != 0)
				c--;
			if(c == 0)
				if(document.getElementById("smsg") != null)
					$("#smsg").remove();
		}, 1000);
		
		/* AJAX */
		/*$(document).ready(function() {
			$('#submitApp').submit(function() {
				var form = $('#submitApp');
				$.ajax({
					type: "POST",
					url: 'db.php',
					data: form.serialize(),
					success: function( response ) {
						console.log( response );
						window.location.replace('application_builder.php?status=success');
					},
					error: function( response ) {
						console.log( response );
					}
				});
			});
		});*/
		/* FIN AJAX */
		/*TRAVEL DATE*/
		function TDate() {
			var ToDate = document.getElementById("inputTTo").value;
			var FromDate = document.getElementById("inputTFrom").value;

			if (new Date(ToDate).getTime() < new Date(FromDate).getTime()) {
				$('#inputTTo').val("");
				return false;
			}
			return true;
		}
		/*ACTIVITY DATE*/
		function ADateF() {
			var ToDate = document.getElementById("inputAFrom").value;
			var FromDate = document.getElementById("inputTFrom").value;

			if (new Date(ToDate).getTime() < new Date(FromDate).getTime()) {
				$('#inputAFrom').val("");
				return false;
			}
			return true;
		}
		function ADateT() {
			var ToDate = document.getElementById("inputATo").value;
			var FromDate = document.getElementById("inputTTo").value;

			if (new Date(ToDate).getTime() > new Date(FromDate).getTime()) {
				$('#inputATo').val("");
				return false;
			}
			return true;
		}
		/*FIN DATES*/
		/*OTHER ACTIVITY CHECK*/
		document.getElementById('pr4dummy').onchange = function() {
			document.getElementById('pr4').disabled = !this.checked;
		};
	</script>
	<!-- End Script -->
</html>