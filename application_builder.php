<?php
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
	/*Get DB connection*/
	include "functions/database.php";
	$conn = connection();
	
	/*Verification functions*/
	include "functions/verification.php";
?>
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
	
	/*get initial character limits for text fields*/
	$appCharMax = getApplicationsMaxLengths($conn);
	$appBudgetCharMax = getApplicationsBudgetsMaxLengths($conn);
	
	$maxName = $appCharMax[array_search('Name', array_column($appCharMax, 0))][1]; //name char limit
	$maxEmail = $appCharMax[array_search('Email', array_column($appCharMax, 0))][1]; //email char limit
	$maxDep = $appCharMax[array_search('Department', array_column($appCharMax, 0))][1]; //department char limit
	$maxDepEmail = $appCharMax[array_search('DepartmentChairEmail', array_column($appCharMax, 0))][1]; //dep email char limit
	$maxTitle = $appCharMax[array_search('Title', array_column($appCharMax, 0))][1]; //title char limit
	$maxDestination = $appCharMax[array_search('Destination', array_column($appCharMax, 0))][1]; //destination char limit
	$maxOtherEvent = $appCharMax[array_search('IsOtherEventText', array_column($appCharMax, 0))][1]; //other event text char limit
	$maxOtherFunding = $appCharMax[array_search('OtherFunding', array_column($appCharMax, 0))][1]; //other funding char limit
	$maxProposalSummary = $appCharMax[array_search('ProposalSummary', array_column($appCharMax, 0))][1]; //proposal summary char limit
	
	$maxBudgetComment = $appBudgetCharMax[array_search('Comment', array_column($appBudgetCharMax, 0))][1]; //budget comment char limit
	
	/*echo "limits: name: ".$maxName.", email: ".$maxEmail.", dep: ".$maxDep.", dep email: ".$maxDepEmail.", title: ".$maxTitle.", 
		destination: ".$maxDestination.", other event: ".$maxOtherEvent.", other funding: ".$maxOtherFunding.", proposal summary: "
		.$maxProposalSummary.", budget comment: ".$maxBudgetComment;
	*/
	
	/*Verify that user is allowed to make an application*/
	if(isUserAllowedToCreateApplication($conn, $_SESSION['broncoNetID'], $_SESSION['position']))
	{
	?>
	<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
			<form enctype="multipart/form-data" class="form-horizontal" id="submitApp" name="submitApp" method="POST" action="controllers/addApplication.php">
				<div ng-controller="budget">
					<!--APPLICANT INFO-->
					<div class="row">
						<h2 class="title">Applicant Information:</h2>
					</div>
					<div class="row">
					<!--NAME-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputName">Name (up to <?php echo $maxName; ?> characters):</label>
								<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" required />
							</div>
						</div>
					<!--EMAIL-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputEmail">Email Address (up to <?php echo $maxEmail; ?> characters):</label>
								<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" required value="<?php echo $_SESSION['email'] ?>"/>
							</div>
						</div>
					<!--DEPARTMENT-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputDept">Department (up to <?php echo $maxDep; ?> characters):</label>
								<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" required />
							</div>
						</div>
					</div>
					<div class="row">
					<!--DEPT MAIL STOP-->
						<div class="col-md-6">
							<div class="form-group">
								<label for="inputDeptM">Department Mail Stop (4 digits):</label>
								<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
					<!--DEPT CHAIR EMAIL-->
						<div class="col-md-6">
							<div class="form-group">
								<label for="inputDeptCE">Department Chair's Email Address (up to <?php echo $maxDepEmail; ?> characters):</label>
								<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" required />
							</div>
						</div>
					</div>
					<!--RESEARCH INFO-->
					<div class="row">
						<h2 class="title">Research Information:</h2>
					</div>
					<div class="row">
					<!--TRAVEL DATES-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTFrom">Travel Date From:</label>
								<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" required />
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTTo">Travel Date To:</label>
								<input type="date" class="form-control" id="inputTTo" name="inputTTo" onchange="TDate()" required />
							</div>
						</div>
					<!--ACTIVITY DATES-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputAFrom">Activity Date From:</label>
								<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" onchange="ADateF()"  required />
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputATo">Activity Date To:</label>
								<input type="date" class="form-control" id="inputATo" name="inputATo" onchange="ADateT()"  required />
							</div>
						</div>
					</div>
					<div class="row">
					<!--TITLE-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputRName">Title of Research (up to <?php echo $maxTitle; ?> characters):</label>
								<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title of Research" required />
							</div>
						</div>
					<!--DESTINATION-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputDest">Destination (up to <?php echo $maxDestination; ?> characters):</label>
								<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" required />
							</div>
						</div>
					<!--AMOUNT REQ-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputAR">Amount Requested($):</label>
								<input type="text" class="form-control" id="inputAR" name="inputAR" placeholder="Enter Amount Requested($)" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
					</div>
					<!--PURPOSE-->
					<div class="row">
						<label for="purposes">Purpose of Travel:</label>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="purpose1" type="checkbox" value="purpose1">Research</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="purpose2" type="checkbox" value="purpose2">Conference</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="purpose3" type="checkbox" value="purpose3">Creative Activity</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="checkbox">
								<label><input name="purposeOtherDummy" id="purposeOtherDummy" type="checkbox" value="purposeOtherDummy">Other, explain.</label>
							</div>
						</div>
						<div class="col-md-10">
							<div class="form-group">
								<label for="purposeOtherText">Explain other purpose (up to <?php echo $maxOtherEvent; ?> characters):</label>
								<input type="text" class="form-control" id="purposeOther" name="purposeOther" disabled="true" placeholder="Enter Explanation" />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="eS">Are you receiving other funding? Who is providing the funds? How much? (up to <?php echo $maxOtherFunding; ?> characters):</label>
								<input type="text" class="form-control" id="eS" name="eS" placeholder="Explain here" />
							</div>
						</div>
					</div>
					<div class="row">
					<!--PROPOSAL SUMMARY-->
						<div class="col-md-12">
							<div class="form-group">
								<label for="props">Proposal Summary (up to <?php echo $maxProposalSummary; ?> characters) (We recommend up to 150 words):</label>
								<textarea class="form-control" id="props" name="props" placeholder="Enter Proposal Summary" rows=10 required /></textarea>
							</div>
						</div>
					</div>
					<div class="row">
						<label for="goals">Please indicate which of the prioritized goals of the IEFDF this proposal fulfills:</label>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal1" type="checkbox" value="goal1">
								Support for international collaborative research and creative activities, or for international research, including archival and field work.</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal2" type="checkbox" value="goal2">
								Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal3" type="checkbox" value="goal3">
								Support for attendance at international conferences, seminars or workshops.</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal4" type="checkbox" value="goal4">
								Support for scholarly international travel in order to enrich international knowledge, which will directly
								contribute to the internationalization of the WMU curricula.</label>
							</div>
						</div>
					</div>
					<div class="row">
						<h2>Budget:(please separate room and board calculating per diem)</h2>
					</div>
					<div class="row">
						<div class="col-md-4">
							<h3>Expense:</h3>
						</div>
						<div class="col-md-4">
							<h3>Comments (up to <?php echo $maxBudgetComment; ?> characters):</h3>
						</div>
						<div class="col-md-4">
							<h3>Amount($):</h3>
						</div>
					</div>
					<div class="row" ng-repeat="bitem in bitems">
						<div class="col-md-4">
							<div class="form-group">
								<select class="form-control" name="{{bitem.ex}}" required />
									<option value="Air Travel">Air Travel</option>
									<option value="Ground Travel">Ground Travel</option>
									<option value="Hotel">Hotel</option>
									<option value="Other">Other</option>
								</select>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<input type="text" class="form-control" name="{{bitem.com}}" placeholder="Explain..." required />
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<input type="text" class="form-control" name="{{bitem.am}}" ng-model="bitem.val" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-5"></div>
						<div id="budgetButtons" class="col-md-2">
							<p id="addBudget"><i class="fa fa-plus-circle fa-2x" aria-hidden="true" ng-click="addInput()"></i></p>
							<p id="removeBudget"><i class="fa fa-minus-circle fa-2x" aria-hidden="true" ng-click="remInput()"></i></p>
						</div>
						<div class="col-md-5"></div>
					</div>
					<div class="row">
						<div class="col-md-5"></div>
						<div class="col-md-2">
							<h3>Total: ${{ getTotal() }}</h3>
						</div>
						<div class="col-md-5"></div>
					</div>
					<!--UPLOAD DOCS FORM-->
					<div class="row">
							<h2>Uploads</h2> 
					</div>
					<div class="row">
						<div class="col-md-6">
								<label for="fD">PROPOSAL NARRATIVE:</label><input type="file" name="fD" id="fD" required/>
						</div>
						<div class="col-md-6">
							<label for="sD">SUPPORTING DOCUMENTS:</label><input type="file" name="sD" id="sD"/>
						</div>
					</div>
					<div class="row">
							<h3>Please ensure to include supporting documentation: Conference letter of acceptance; invitation letter for research or creative activity, etc.</h3> 
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
								echo '<span class="lt" style="color: green; font-size: 22px;" id="smsg"><b>Application Submitted.</b></span>';
							}
						}
					?>
					<span id="loadSpinner" class="lt" style="visibility: hidden;">Submitting... <i class="fa fa-spinner fa-spin" style="font-size:35px !important;"></i></span>
				</center>
			</form>
		</div>
		<!--BODY-->
	
	<?php
	}
	else{
	?>
		<h1>You can not fill out a new application!</h1>
	<?php
	}
	?>
	</body>
	
	<!-- AngularJS Script -->
	<script>
		
		var myApp = angular.module('HIGE-app', []);
		
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
		/*activate 'other purpose' box when corresponding checkbox is checked*/
		document.getElementById('purposeOtherDummy').onchange = function() {
			document.getElementById('purposeOther').disabled = !this.checked;
		};
	</script>
	<!-- End Script -->
</html>
<?php
	$conn = null; //close connection
?>