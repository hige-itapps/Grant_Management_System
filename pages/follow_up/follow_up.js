//init app
var higeApp = angular.module('HIGE-app', []);

/*App Controller*/
higeApp.controller('reportCtrl', ['$scope', '$http', '$sce', '$filter', function($scope, $http, $sce){
    //get PHP init variables
   
    //char limits
    $scope.maxProjectSummary = scope_maxProjectSummary;
    //user permissions
    $scope.isCreating = scope_isCreating;
    $scope.isReviewing = scope_isReviewing;
    $scope.isAdmin = scope_isAdmin;
    $scope.isCommittee = scope_isCommittee;
    $scope.isChairReviewing = scope_isChairReviewing;
    $scope.isFollowUpApprover = scope_isFollowUpApprover;
    //previous application data
    var app = var_app;
    //for when not creating report
    var report = var_report;
    if(report != null){
        report.reportFiles = var_reportFiles;
        report.reportEmails = var_reportEmails;//previously sent emails
    }

    //get staff notes if they exist
    $scope.staffNotes = scope_staffNotes;

    //set admin updating to false by default
    $scope.isAdminUpdating = false;

    //the function to use when submitting; set this parameter in ng-click on the submit buttons
    $scope.submitFunction = null;



    

    /*Functions*/

    //submit the report - use a different function depending on the submitFunction variable
    $scope.submit = function(){
        if($scope.submitFunction === 'insertReport'){$scope.insertReport();}
        else if($scope.submitFunction === 'approveReport'){$scope.approveReport('Approved');}
        else if($scope.submitFunction === 'denyReport'){$scope.approveReport('Denied');}
        else if($scope.submitFunction === 'holdReport'){$scope.approveReport('Hold');}
        else if($scope.submitFunction === 'uploadFiles'){$scope.uploadFiles();}
    }

    //remove the alert from the page
    $scope.removeAlert = function(){
        $scope.alertMessage = null;
    }

    //display a generic loading alert to the page
    $scope.loadingAlert = function(){
        $scope.alertType = "info";
        $scope.alertMessage = "Loading...";
    }

    //function to turn on/off admin updating
    $scope.toggleAdminUpdate = function(){
        $scope.isAdmin = !$scope.isAdmin; //toggle the isAdmin permission
        $scope.isAdminUpdating = !$scope.isAdmin; //set the isAdminUpdating permission to the opposite of isAdmin
        $scope.reportFieldsDisabled = $scope.isAdmin; //update the fields to be editable or non-editable
    }

    //redirect the user to the homepage. Optionally, send an alert which will show up on the next page, consisting of a type(success, warning, danger, etc.) and message
    $scope.redirectToHomepage = function(alert_type, alert_message){
        var homeURL = '../home/home.php'; //url to homepage

        if(alert_type == null) //if no alert message to send, simply redirect
        {
            if($scope.isAdmin || $scope.isAdminUpdating || $scope.isCreating || $scope.isReviewing || $scope.isFollowUpApprover)
            {
                if(!confirm ('Are you sure you want to leave this page? Any unsaved data will be lost.')){return;} //don't leave page if user decides not to
            }
            window.location.replace(homeURL);
        }
        else //if there IS an alert message to send, fill out an invisible form & submit so the data can be sent as POST
        {
            var form = $('<form type="hidden" action="' + homeURL + '" method="post">' +
                '<input type="text" name="alert_type" value="' + alert_type + '" />' +
                '<input type="text" name="alert_message" value="' + alert_message + '" />' +
            '</form>');
            $('body').append(form);
            form.submit();
        }
    }

    //Remove a file from the docs array
    $scope.removeDoc = function(index){
        $scope.uploadDocs.splice(index, 1); //splice element from array
    }

    //fill in the form with report data; send existing data in to be populated, or if nothing is given, attempt to retrieve the most up-to-date report data with an AJAX call. Ignore fields that never change
    $scope.populateForm = function(existingReport){

        //first, get the report if it doesn't already exist
        if(existingReport == null || typeof existingReport === 'undefined')
        {
            $http({
                method  : 'POST',
                url     : '/../../ajax/get_follow_up_report.php',
                data    : $.param({appID: app.id}),  // pass in data as strings
                headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
            })
            .then(function (response) {
                console.log(response, 'res');
                //data = response.data;
                if(typeof response["error"] !== 'undefined'){ //there was an error
                    $scope.alertType = "danger";
                    $scope.alertMessage = "There was an error when trying to retrieve the report: " + response["error"];
                }else{ //no error
                    $scope.populateForm(response.data);//recurse to this function again with a real app
                }
            },function (error){
                console.log(error, 'can not get data.');
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an unexpected error when trying to retrieve the report: " + error;
            });
        }
        else //if it exists, then populate form
        {
            try
            {
                //dates require a bit of extra work to convert properly! Javascript offsets the dates based on timezones, and one way to combat that is by replacing hyphens with slashes (don't ask me why)
                $scope.formData.travelFrom = new Date(existingReport.travelFrom.replace(/-/g, '\/'));
                $scope.formData.travelTo = new Date(existingReport.travelTo.replace(/-/g, '\/'));
                $scope.formData.activityFrom = new Date(existingReport.activityFrom.replace(/-/g, '\/'));
                $scope.formData.activityTo = new Date(existingReport.activityTo.replace(/-/g, '\/'));
                $scope.formData.amountAwardedSpent = existingReport.amountAwardedSpent;
                $scope.formData.projectSummary = existingReport.projectSummary;

                $scope.formData.deptChairApproval = existingReport.deptChairApproval;
                $scope.formData.amountAwarded = existingReport.amountAwarded;
                
                $scope.reportFiles = existingReport.reportFiles;//refresh the associated files

                existingReport.reportEmails.forEach(function (email){ //iterate over sent emails
                    email[3] = $sce.trustAsHtml(email[3]); //allow html to render correctly
                    email[4] = new Date(email[4] + ' UTC').toString();//convert timestamp to local time
                });
                $scope.reportEmails = existingReport.reportEmails;//refresh the associated emails
                $scope.reportStatus = existingReport.status; //set the report's status
            }
            catch(e)
            {
                console.log(e);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an unexpected error when trying to populate the form: " + e;
            }
        }
    }

    // process the form (AJAX request) - either for new insertion or updating
    $scope.insertReport = function() {

        if(confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.'))
        {
            //if this is a new report, check to see if any documents have been set. Give a warning confirmation just in case if they aren't.
            var fd = new FormData();
            var totalDocs = 0; //iterate for each new  document
            Object.keys($scope.uploadDocs).forEach(function (key){ //iterate over documents
                fd.append('followUpDoc'+key, $scope.uploadDocs[key]); //save files in FormData object
                totalDocs++;
            });

            if($scope.isCreating)//creating, not updating
            {
                //a warning for missing files
                if(totalDocs === 0)
                {  
                    if(!confirm( "Warning: you have not selected any files to upload with your follow up report. Are you sure you want to submit anyway?")) {return;} //exit function early if not confirmed
                }
            }

            //start a loading alert
            $scope.loadingAlert();

            //add necessary form elements to the FormData array
            if($scope.formData.travelFrom != null){fd.append("travelFrom", JSON.stringify($scope.formData.travelFrom.getTime()/1000));}
            if($scope.formData.travelTo != null){fd.append("travelTo", JSON.stringify($scope.formData.travelTo.getTime()/1000));}
            if($scope.formData.activityFrom != null){fd.append("activityFrom", JSON.stringify($scope.formData.activityFrom.getTime()/1000));}
            if($scope.formData.activityTo != null){fd.append("activityTo", JSON.stringify($scope.formData.activityTo.getTime()/1000));}
            fd.append("amountAwardedSpent", JSON.stringify($scope.formData.amountAwardedSpent));
            fd.append("projectSummary", JSON.stringify($scope.formData.projectSummary));
            fd.append("appID", JSON.stringify(app.id));

            $http({
                method  : 'POST',
                url     : '/../../ajax/submit_follow_up_report.php',
                data    : fd,  // pass in the FormData object
                transformRequest: angular.identity,
                headers : { 'Content-Type': undefined,'Process-Data': false}  //allow for file and text upload
            })
            .then(function (response) {
                console.log(response, 'res');
                //data = response.data;
                if(typeof response.data.success === 'undefined') //unexpected result!
                {
                    console.log(JSON.stringify(response, null, 4));
                    $scope.alertType = "danger";
                    $scope.alertMessage = "There was an unexpected error with your submission! Server response: " + JSON.stringify(response, null, 4);
                }
                else if(response.data.success)
                {
                     //check for fileUpload success too
                     $scope.errors = []; //clear any old errors
                     var newAlertType = null;
                     var newAlertMessage = null;
 
                     if(response.data.fileSuccess)
                     {
                         newAlertType = "success";
                         newAlertMessage = "Success! The report has been received. You can return to your report at any time to upload more documents if necessary.";
                     }
                     else
                     {
                         newAlertType = "warning";
                         newAlertMessage = "Warning: The report has been received, but there was an error when trying to upload your documents. You can return to your report to upload more documents. Error: " + response.data.fileError;
                     }
                     if(!$scope.isCreating) //updating
                     {
                         $scope.populateForm(null);//refresh form so that new files show up
                         $scope.uploadDocs = []; //empty array
                         $scope.alertType = newAlertType;
                         $scope.alertMessage = newAlertMessage;
                     }
                     else //creating
                     {
                         $scope.redirectToHomepage(newAlertType, newAlertMessage); //redirect to the homepage with the message
                     }
                }
                else
                {
                    $scope.errors = response.data.errors;
                    $scope.alertType = "danger";
                    if(typeof $scope.errors["other"] !== 'undefined') //there was an 'other' (non-normal) error
                    {
                        if(Object.keys($scope.errors).length === 1){$scope.alertMessage = "There was an uncommon error with your submission: " + $scope.errors["other"];}//just the other error
                        else{$scope.alertMessage = "There was an error with your submission, please double check your form for errors, then try resubmitting. In addition, there was an uncommon error with your submission: " + $scope.errors["other"];}//the other error + normal errors
                    }
                    else {$scope.alertMessage = "There was an error with your submission, please double check your form for errors, then try resubmitting.";}//just normal errors
                }
            },function (error){
                console.log(error, 'can not get data.');
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an unexpected error when trying to insert the report!";
            });
        }
    };


    //approve or deny report with the status parameter
    $scope.approveReport = function(status){

        if(confirm ("By confirming, your email will be sent to the applicant! Are you sure you want to set this report's status to " + status + "?"))
        {
            //start a loading alert
            $scope.loadingAlert();

            $http({
                method  : 'POST',
                url     : '/../../ajax/approve_follow_up_report.php',
                data    : $.param({appID: app.id, status: status, emailAddress: $scope.formData.email, emailMessage: $scope.formData.approverEmail}),  // pass in data as strings
                headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
            })
            .then(function (response) {
                console.log(response, 'res');
                if(typeof response.data.error === 'undefined') //ran function as expected
                {
                    if(response.data.success === true)//updated
                    {
                        if(response.data.email.saveSuccess === true) //email saved correctly
                        {
                            if(response.data.email.sendSuccess === true) //email was sent correctly
                            {
                                $scope.alertType = "success";
                                $scope.alertMessage = "Success! The report's status has been updated to: \"" + status + "\". The email was successfully saved and sent out to the applicant.";
                            }
                            else
                            {
                                $scope.alertType = "warning";
                                $scope.alertMessage = "Warning: The report's status was successfully updated to: \"" + status + "\", and the email was saved, but it could not be sent out to the applicant. Error: " + response.data.email.sendError;
                            }
                        }
                        else
                        {
                            $scope.alertType = "warning";
                            $scope.alertMessage = "Warning: The report's status was successfully updated to: \"" + status + "\", but the email was neither saved nor sent out to the applicant.";
                        }
                        $scope.populateForm(); //refresh the form again
                    }
                    else//didn't update
                    {
                        $scope.alertType = "warning";
                        $scope.alertMessage = "Warning: The report may not have been updated from its previous state.";
                    }
                }
                else //failure!
                {
                    console.log(response.data.error);
                    $scope.alertType = "danger";
                    $scope.alertMessage = "There was an error with your approval! Error: " + response.data.error;
                }
            },function (error){
                console.log(error, 'can not get data.');
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an unexpected error with your approval!";
            });
        }
    };


     //let a staff member save a note
     $scope.saveNote = function(){

        console.log("saving note...");

        //start a loading alert
        $scope.loadingAlert();

        $http({
            method  : 'POST',
            url     : '/../../ajax/save_note.php',
            data    : $.param({appID: app.id, note: $scope.staffNotes[1]}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//saved
                {
                    $scope.alertType = "success";
                    $scope.alertMessage = "Your note has been saved.";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: The note could not be updated from its previous state.";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to save your note! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
            $scope.alertType = "danger";
            $scope.alertMessage = "There was an unexpected error when trying to save your note!";
        });
    };


    //let the creator or admin upload files for this report
    $scope.uploadFiles = function(){

        if(confirm ('Are you sure you want to upload the selected files?')) //upload warning
        {
            //start a loading alert
            $scope.loadingAlert();

            var fd = new FormData();
            var totalUploads = 0; //iterate for each new file
            Object.keys($scope.uploadDocs).forEach(function (key){ //iterate over supporting documents
                fd.append('followUpDoc'+key, $scope.uploadDocs[key]); //save files in FormData object
                totalUploads++;
            });
            fd.append('appID', app.id);

            if(totalUploads > 0) //at least 1 new file
            {
                $http({
                    method  : 'POST',
                    url     : '/../../ajax/upload_file.php',
                    data    : fd,  // pass in the FormData object
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined,'Process-Data': false} //allow for file upload
                })
                .then(function (response) {
                    console.log(response, 'res');
                    if(typeof response.data.error === 'undefined') //ran function as expected
                    {
                        response.data = response.data.trim();//remove blankspace around data
                        if(response.data === "true")//updated
                        {
                            $scope.alertType = "success";
                            $scope.alertMessage = "Success! Your files have been uploaded.";
                            $scope.uploadDocs = []; //empty array
                        }
                        else if(response.data === "false")//didn't update
                        {
                            $scope.alertType = "warning";
                            $scope.alertMessage = "Warning: At least 1 selected file was not uploaded.";
                        }
                        else
                        {
                            $scope.alertType = "danger";
                            $scope.alertMessage = "There was an unexpected error with your upload! Error: " + response.data;
                        }
                        $scope.populateForm(null);//refresh form so that new files show up
                    }
                    else //failure!
                    {
                        console.log(response.data.error);
                        $scope.alertType = "danger";
                        $scope.alertMessage = "There was an error with your upload! Error: " + response.data.error;
                    }
                },function (error){
                    console.log(error, 'can not get data.');
                    $scope.alertType = "danger";
                    $scope.alertMessage = "There was an unexpected error when trying to upload your files!";
                });
            }
            else
            {
                $scope.alertType = "warning";
                $scope.alertMessage = "Warning: No new files selected to upload.";
            }
        }
    };


    //let anyone on the page download one of the associated files. NOTE- this technically isn't AJAX, just a php redirection, since AJAX file downloads from the server aren't possible.
    $scope.downloadFile = function(filename){
        window.location.href = "/../../ajax/download_file.php?appID="+app.id+"&filename="+filename; //redirect to download script
    };



    /*On startup*/

    // create a blank object to hold our form information
    // $scope will allow this to pass between controller and view
    $scope.formData = {};
    $scope.errors = {};//all current errors
    $scope.uploadDocs = []; //array of new supporting docs

    //set the static fields
    $scope.formData.name = app.name;
    $scope.formData.email = app.email;
    $scope.formData.department = app.department;
    $scope.formData.deptChairEmail = app.deptChairEmail;
    $scope.formData.title = app.title;
    $scope.formData.destination = app.destination;

    /*If not creating, get report data and populate entire form. Also alert user with warning*/
    if(!$scope.isCreating)
    {
        $scope.reportStatus = report.status; //set the report's status
        $scope.reportFieldsDisabled = true; //disable report inputs

        $scope.populateForm(report); //populate the form with the report data
    }
    else //otherwise alert user with warning
    {
        alert("Please complete your final report fully; reports in progress cannot be saved. Additional documents may be uploaded later if preferred, however. Uploaded files cannot be deleted once submitted.");
    }
}]);



//Custom directive for files, originally from: https://stackoverflow.com/questions/33534497/file-upload-using-angularjs-with-php-server-script
//Needed to encrypt files as multipart/formdata, so that they are sent like other form elements. This eliminates the need to upload over SFTP separately. Modified to work with multiple files at once
//This one specifically supports many file uploads at once
higeApp.directive('readdocuments', ['$parse', function ($parse) {
    return {
    restrict: 'A',
    link: function(scope, element, attrs) {
        var model = $parse(attrs.readdocuments);
        var modelSetter = model.assign;

        element.bind('change', function(){
            scope.$apply(function(){
                var newFiles = Array.from(element[0].files) //save the new files in an array, not a FileList
                var allFiles = scope[attrs.readdocuments].concat(newFiles); //add new files to the full array
                //console.log(allFiles);
                modelSetter(scope, allFiles); //set to all files

                element[0].value = null; //reset value (so it doesn't try to remember previous state)
            });
        });
    }
};
}]);