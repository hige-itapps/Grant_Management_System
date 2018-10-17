//init app
var higeApp = angular.module('HIGE-app', []);

/*App Controller*/
higeApp.controller('appCtrl', ['$scope', '$http', '$sce', '$filter', function($scope, $http, $sce, $filter){
    //get PHP init variables
    $scope.currentDate = scope_currentDate;
    $scope.maxUploadSize = scope_maxUploadSize;
    $scope.uploadTypes = scope_uploadTypes;
    //either from the submit date or the current date
    $scope.thisCycle = scope_thisCycle;
    $scope.nextCycle = scope_nextCycle;
    //char limits
    $scope.maxName = scope_maxName;
    $scope.maxDepartment = scope_maxDepartment;
    $scope.maxTitle = scope_maxTitle;
    $scope.maxDestination = scope_maxDestination;
    $scope.maxOtherEvent = scope_maxOtherEvent;
    $scope.maxOtherFunding = scope_maxOtherFunding;
    $scope.maxProposalSummary = scope_maxProposalSummary;
    $scope.maxDeptChairApproval = scope_maxDeptChairApproval;
    $scope.maxBudgetDetails = scope_maxBudgetDetails;
    //user permissions
    $scope.isCreating = scope_isCreating;
    $scope.isReviewing = scope_isReviewing;
    $scope.isAdmin = scope_isAdmin;
    $scope.isCommittee = scope_isCommittee;
    $scope.isChair = scope_isChair;
    $scope.isChairReviewing = scope_isChairReviewing;
    $scope.isApprover = scope_isApprover;
    //for when not creating application
    var app = var_app;
    if(app != null){
        app.appFiles = var_appFiles;
        app.appEmails = var_appEmails;//previously sent emails
    }
    //for when creating application
    var CASemail = var_CASemail;
    $scope.allowedFirstCycle = scope_allowedFirstCycle;
    $scope.shouldWarn = scope_shouldWarn;

    //get staff notes if they exist
    $scope.staffNotes = scope_staffNotes;

    //set admin updating to false by default
    $scope.isAdminUpdating = false;

    //the function to use when submitting; set this parameter in ng-click on the submit buttons
    $scope.submitFunction = null;



    
    /*Functions*/

    //submit the application - use a different function depending on the submitFunction variable
    $scope.submit = function(){
        if($scope.submitFunction === 'insertApplication'){$scope.insertApplication();}
        else if($scope.submitFunction === 'approveApplication'){$scope.approveApplication('Approved');}
        else if($scope.submitFunction === 'denyApplication'){$scope.approveApplication('Denied');}
        else if($scope.submitFunction === 'holdApplication'){$scope.approveApplication('Hold');}
        else if($scope.submitFunction === 'chairApproval'){$scope.chairApproval();}
        else if($scope.submitFunction === 'uploadFiles'){$scope.uploadFiles();}
    }

    //Add new budget item
    $scope.addBudgetItem = function(expense, details, amount) {
        if(typeof expense === 'undefined'){expense = "Other";}
        if(typeof details === 'undefined'){details = "";}
        if(typeof amount === 'undefined'){amount = 0;}
        $scope.formData.budgetItems.push({
            expense: expense,
            details: details,
            amount: amount
        })       
    }
    //Remove last budget item
    $scope.removeBudgetItem = function(index) {
        if($scope.formData.budgetItems.length > 1)
        {
            $scope.formData.budgetItems.splice(index, 1);
        }  
    }
    //Get total budget cost
    $scope.getTotal = function(){
        var total = 0;
        for(var i = 0; i < $scope.formData.budgetItems.length; i++){
            var newVal = parseFloat($scope.formData.budgetItems[i]["amount"]);
            if(!isNaN(newVal)){total += newVal;}
        }
        return (total).toFixed(2);
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
        $scope.appFieldsDisabled = $scope.isAdmin; //update the fields to be editable or non-editable
    }

    //redirect the user to the homepage. Optionally, send an alert which will show up on the next page, consisting of a type(success, warning, danger, etc.) and message
    $scope.redirectToHomepage = function(alert_type, alert_message){
        var homeURL = '../home/home.php'; //url to homepage

        if(alert_type == null) //if no alert message to send, simply redirect
        {
            if($scope.isAdmin || $scope.isAdminUpdating || $scope.isCreating || $scope.isReviewing || $scope.isApprover || $scope.isChair)
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

    //Remove a file from the supporting docs array
    $scope.removeSupportingDoc = function(index){
        $scope.uploadSupportingDocs.splice(index, 1); //splice element from array
    }

    //Remove the chosen proposal narrative
    $scope.removeProposalNarrative = function(){
        $scope.uploadProposalNarrative = []; //empty array
    }

    //fill in the form with app data; send existing data in to be populated, or if nothing is given, attempt to retrieve the most up-to-date app data with an AJAX call
    $scope.populateForm = function(existingApp){

        //first, get the application if it doesn't already exist
        if(existingApp == null || typeof existingApp === 'undefined')
        {
            $http({
                method  : 'POST',
                url     : '../api.php?get_application',
                data    : $.param({appID: $scope.formData.updateID}),  // pass in data as strings
                headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
            })
            .then(function (response) {
                console.log(response, 'res');
                //data = response.data;
                if(typeof response["error"] !== 'undefined'){ //there was an error
                    $scope.alertType = "danger";
                    $scope.alertMessage = "There was an error when trying to retrieve the application: " + response["error"];
                }else{ //no error
                    $scope.populateForm(response.data);//recurse to this function again with a real app
                }
            },function (error){
                console.log(error, 'can not get data.');
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an unexpected error when trying to retrieve the application: " + error;
            });
        }
        else //if it exists, then populate form
        {
            try
            {
                //console.log(existingApp);
                $scope.formData.cycleChoice = parseInt(existingApp.nextCycle) ? "next" : "this"; //1 = next, 0 = this
                $scope.formData.name = existingApp.name;
                $scope.formData.email = existingApp.email;
                $scope.formData.department = existingApp.department;
                $scope.formData.deptChairEmail = existingApp.deptChairEmail;
                //dates require a bit of extra work to convert properly! Javascript offsets the dates based on timezones, and one way to combat that is by replacing hyphens with slashes (don't ask me why)
                /*alert(new Date(existingApp.travelFrom));
                alert(new Date(existingApp.travelFrom.replace(/-/g, '\/')));*/
                $scope.formData.travelFrom = new Date(existingApp.travelFrom.replace(/-/g, '\/'));
                $scope.formData.travelTo = new Date(existingApp.travelTo.replace(/-/g, '\/'));
                $scope.formData.activityFrom = new Date(existingApp.activityFrom.replace(/-/g, '\/'));
                $scope.formData.activityTo = new Date(existingApp.activityTo.replace(/-/g, '\/'));
                $scope.formData.title = existingApp.title;
                $scope.formData.destination = existingApp.destination;
                $scope.formData.amountRequested = existingApp.amountRequested;
                //check boxes using conditional (saved as numbers; need to be converted to true/false)
                $scope.formData.purpose1 = parseInt(existingApp.purpose1) ? true : false;
                $scope.formData.purpose2 = parseInt(existingApp.purpose2) ? true : false;
                $scope.formData.purpose3 = parseInt(existingApp.purpose3) ? true : false;
                $scope.formData.purpose4OtherDummy = existingApp.purpose4 ? true : false; //set to true if any value exists
                $scope.formData.purpose4Other = existingApp.purpose4;
                $scope.formData.otherFunding = existingApp.otherFunding;
                $scope.formData.proposalSummary = existingApp.proposalSummary;
                $scope.formData.goal1 = parseInt(existingApp.goal1) ? true : false;
                $scope.formData.goal2 = parseInt(existingApp.goal2) ? true : false;
                $scope.formData.goal3 = parseInt(existingApp.goal3) ? true : false;
                $scope.formData.goal4 = parseInt(existingApp.goal4) ? true : false;

                $scope.formData.budgetItems = []; //empty budget items array
                //add the budget items
                for(var i = 0; i < existingApp.budget.length; i++) {
                    $scope.addBudgetItem(existingApp.budget[i][2], existingApp.budget[i][4], existingApp.budget[i][3]);
                }
    
                $scope.formData.deptChairApproval = existingApp.deptChairApproval;
                $scope.formData.amountAwarded = existingApp.amountAwarded;
                
                $scope.appFiles = existingApp.appFiles;//refresh the associated files
                $scope.appStatus = existingApp.status;//refresh the status

                existingApp.appEmails.forEach(function (email){ //iterate over sent emails
                    email[3] = $sce.trustAsHtml(email[3]); //allow html to render correctly
                    email[4] = new Date(email[4] + ' UTC').toString();//convert timestamp to local time
                });
                $scope.appEmails = existingApp.appEmails;//refresh the associated emails
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
    $scope.insertApplication = function() {

        if(confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.')){
            //if this is a new application, check to see if any proposal narratives/supporting documents have been set. Give a warning confirmation just in case if they aren't.
            var fd = new FormData();
            var totalSupportingDocs = 0; //iterate for each new supporting document
            var totalProposalNarratives = 0; //iterate for each new proposal narrative (there should probably only be 1)
            Object.keys($scope.uploadSupportingDocs).forEach(function (key){ //iterate over supporting documents
                fd.append('supportingDoc'+key, $scope.uploadSupportingDocs[key]); //save files in FormData object
                totalSupportingDocs++;
            });
            Object.keys($scope.uploadProposalNarrative).forEach(function (key){ //iterate over proposal narratives (should be limited to just 1 by default)
                fd.append('proposalNarrative'+key, $scope.uploadProposalNarrative[key]); //save files in FormData object
                totalProposalNarratives++;
            });

            if(typeof $scope.formData.updateID === 'undefined'){//creating, not updating
                var warningConfirmation = null; //a warning for missing files
                if(totalSupportingDocs === 0 && totalProposalNarratives === 0){ warningConfirmation = "Warning: you have not selected any files to upload with your application. Are you sure you want to submit anyway?";}
                else if(totalSupportingDocs === 0){ warningConfirmation = "Warning: you have not selected any supporting documents to upload with your application. Are you sure you want to submit anyway?";}
                else if(totalProposalNarratives === 0){ warningConfirmation = "Warning: you have not selected a proposal narrative to upload with your application. Are you sure you want to submit anyway?";}

                if(warningConfirmation != null){//something is missing, so better confirm
                    if(!confirm(warningConfirmation)) {return;} //exit function early if not confirmed
                }
            }

            //start a loading alert
            $scope.loadingAlert();

            //loop through form data, appending each field to the FormData object
            for (var key in $scope.formData) {
                if ($scope.formData.hasOwnProperty(key)) {
                    //Check if this is one of the dates, because if so, we need to format it differently since dates aren't correctly serialized, see: https://stackoverflow.com/questions/11893083/convert-normal-date-to-unix-timestamp
                    if(key == "travelFrom" || key == "travelTo" || key == "activityFrom" || key == "activityTo"){
                        //console.log(key + " -x-> " + JSON.stringify($scope.formData[key].getTime()/1000));
                        if($scope.formData[key] != null){fd.append(key, JSON.stringify($scope.formData[key].getTime()/1000));}
                    }
                    else{
                        //console.log(key + " -> " + JSON.stringify($scope.formData[key]));
                        fd.append(key, JSON.stringify($scope.formData[key]));
                    }
                }
            }

            $http({
                method  : 'POST',
                url     : '../api.php?submit_application',
                data    : fd,  // pass in the FormData object
                transformRequest: angular.identity,
                headers : { 'Content-Type': undefined,'Process-Data': false}  //allow for file and text upload
            })
            .then(function (response) {
                console.log(response, 'res');
                //data = response.data;
                if(typeof response.data.insert === 'undefined' || typeof response.data.insert.success === 'undefined'){//unexpected result!
                    console.log(JSON.stringify(response, null, 4));
                    $scope.alertType = "danger";
                    $scope.alertMessage = "There was an unexpected error with your submission! Server response: " + JSON.stringify(response, null, 4);
                }
                else if(response.data.insert.success){
                    $scope.errors = []; //clear any old errors
                    var newAlertType = null;
                    var newAlertMessage = null;

                    var fileError = ''; //set to the file upload error if there is one
                    var emailSaveError = ''; //set to email save error if there is one
                    var emailSendError = ''; //set to email send error if there is one

                    //check for fileUpload success
                    if(!response.data.fileSuccess){//error when uploading files
                        fileError = response.data.fileError;
                    }
                    //check for email saving success and sending success if creating
                    if($scope.isCreating){
                        if(!response.data.email.saveSuccess){ //failed save
                            emailSaveError = response.data.email.saveError;
                        }
                        else if(!response.data.email.saveSuccess){ //failed send
                            emailSendError = response.data.email.sendError;
                        }
                    }

                    //if all successful
                    if(!fileError && !emailSaveError && !emailSendError){
                        newAlertType = "success";
                        newAlertMessage = "Success! The application has been received.";
                        if($scope.isCreating){
                            newAlertMessage += " Your specified department chair has been notified of your submission. You can return to your application at any time to upload more documents if necessary.";
                        }
                    }
                    else{ //a failure
                        newAlertType = "warning";
                        newAlertMessage = "Warning: The application has been received, but there was a problem with it:";
                        //possibly multiple errors
                        if(fileError){
                            newAlertMessage += '\n' + fileError;
                        }
                        if(emailSaveError){
                            newAlertMessage += '\n' + emailSaveError;
                        }
                        if(emailSendError){
                            newAlertMessage += '\n' + emailSendError;
                        }

                        //if($scope.isCreating){ //append more to the message if creating
                            newAlertMessage += '\n';
                            //if no email errors
                            if(!emailSaveError && !emailSendError){
                                newAlertMessage += "Your specified department chair has been notified of your submission. ";
                            }
                            newAlertMessage += "You can return to your application at any time to upload more documents if necessary.";
                        //}
                    }
                    
                    if(!$scope.isCreating){//updating
                        $scope.populateForm(null);//refresh form so that new files show up
                        $scope.uploadProposalNarrative = []; //empty array
                        $scope.uploadSupportingDocs = []; //empty array
                        $scope.alertType = newAlertType;
                        $scope.alertMessage = newAlertMessage;
                    }
                    else{//creating
                        $scope.redirectToHomepage(newAlertType, newAlertMessage); //redirect to the homepage with the message
                    }
                   
                }
                else{
                    $scope.errors = response.data.insert.errors;
                    $scope.alertType = "danger";
                    if(typeof $scope.errors["other"] !== 'undefined'){//there was an 'other' (non-normal) error
                        if(Object.keys($scope.errors).length === 1){$scope.alertMessage = "There was an uncommon error with your submission: " + $scope.errors["other"];}//just the other error
                        else{$scope.alertMessage = "There was an error with your submission, please double check your form for errors, then try resubmitting. In addition, there was an uncommon error with your submission: " + $scope.errors["other"];}//the other error + normal errors
                    }
                    else {$scope.alertMessage = "There was an error with your submission, please double check your form for errors, then try resubmitting.";}//just normal errors
                }
            },function (error){
                console.log(error, 'can not get data.');
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an unexpected error when trying to insert the application!";
            });
        }
    };



    //delete this entire application -- only admins are capable of doing this
    $scope.deleteApplication = function(){
        var retVal = prompt("WARNING - BY DELETING THIS APPLICATION, EVERYTHING ASSOCIATED WITH THIS APPLICATION WILL BE WIPED FROM THE DATABASE! YOU WILL NOT BE ABLE TO UNDO THIS OPERATION! To confirm, please type 'DELETE' into the confirmation box: ", "confirm delete");
        if(retVal !== "DELETE"){
            return; //exit early if not confirmed
        }
        //continue if confirmed, start a loading alert
        $scope.loadingAlert();

        $http({
            method  : 'POST',
            url     : '../api.php?remove_application',
            data    : $.param({appID: $scope.formData.updateID}),  // pass in appID string
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                var newAlertType = "success";
                var newAlertMessage = "Success! The application was deleted.";
                $scope.redirectToHomepage(newAlertType, newAlertMessage); //redirect to the homepage with the message
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to delete this application! Error: " + response.data.error;
                $scope.populateForm(); //refresh the form again
            }
        },function (error){
            console.log(error, 'can not get data.');
            $scope.alertType = "danger";
            $scope.alertMessage = "There was an unexpected error when trying to delete this application!";
        });
    }


    //approve, hold, or deny application with the status parameter
    $scope.approveApplication = function(status){

        if(confirm ("By confirming, your email will be sent to the applicant! Are you sure you want to set this application's status to " + status + "?"))
        {
            //start a loading alert
            $scope.loadingAlert();

            $http({
                method  : 'POST',
                url     : '../api.php?approve_application',
                data    : $.param({appID: $scope.formData.updateID, status: status, amount: $scope.formData.amountAwarded, emailAddress: $scope.formData.email, emailMessage: $scope.formData.approverEmail}),  // pass in data as strings
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
                                $scope.alertMessage = "Success! The application's status has been updated to: \"" + status + "\". The email was successfully saved and sent out to the applicant.";
                            }
                            else
                            {
                                $scope.alertType = "warning";
                                $scope.alertMessage = "Warning: The application's status was successfully updated to: \"" + status + "\", and the email was saved, but it could not be sent out to the applicant. Error: " + response.data.email.sendError;
                            }
                        }
                        else
                        {
                            $scope.alertType = "warning";
                            $scope.alertMessage = "Warning: The application's status was successfully updated to: \"" + status + "\", but the email was neither saved nor sent out to the applicant.";
                        }
                    }
                    else//didn't update
                    {
                        $scope.alertType = "warning";
                        $scope.alertMessage = "Warning: The application may not have been updated from its previous state.";
                    }
                }
                else //failure!
                {
                    console.log(response.data.error);
                    $scope.alertType = "danger";
                    $scope.alertMessage = "There was an error with your approval! Error: " + response.data.error;
                }
                $scope.populateForm(); //refresh the form again
            },function (error){
                console.log(error, 'can not get data.');
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an unexpected error with your approval!";
            });
        }
    };


    //let the department chair approve this application
    $scope.chairApproval = function(){

        if(confirm ('Are you sure you want to approve this application?'))
        {
            //start a loading alert
            $scope.loadingAlert();

            $http({
                method  : 'POST',
                url     : '../api.php?chair_approval',
                data    : $.param({appID: $scope.formData.updateID, deptChairApproval: $scope.formData.deptChairApproval}),  // pass in data as strings
                headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
            })
            .then(function (response) {
                console.log(response, 'res');
                if(typeof response.data.error === 'undefined') //ran function as expected
                {
                    response.data = response.data.trim();//remove blankspace around data
                    if(response.data === "true")//updated
                    {
                        $scope.redirectToHomepage("success", "Success! You have approved this application."); //redirect to the homepage with the message
                    }
                    else//didn't update
                    {
                        $scope.alertType = "warning";
                        $scope.alertMessage = "Warning: The application was not updated from its previous state.";
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
            url     : '../api.php?save_note',
            data    : $.param({appID: $scope.formData.updateID, note: $scope.staffNotes[1]}),  // pass in data as strings
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


    //let the creator or admin upload files for this application
    $scope.uploadFiles = function(){

        if(confirm ('Are you sure you want to upload the selected files?')) //upload warning
        {
            //start a loading alert
            $scope.loadingAlert();

            var fd = new FormData();
            var totalUploads = 0; //iterate for each new file
            Object.keys($scope.uploadSupportingDocs).forEach(function (key){ //iterate over supporting documents
                fd.append('supportingDoc'+key, $scope.uploadSupportingDocs[key]); //save files in FormData object
                totalUploads++;
            });
            Object.keys($scope.uploadProposalNarrative).forEach(function (key){ //iterate over proposal narratives (should be limited to just 1 by default)
                fd.append('proposalNarrative'+key, $scope.uploadProposalNarrative[key]); //save files in FormData object
                totalUploads++;
            });
            fd.append('appID', $scope.formData.updateID);

            if(totalUploads > 0) //at least 1 new file
            {
                $http({
                    method  : 'POST',
                    url     : '../api.php?upload_file',
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
                    }
                    else //failure!
                    {
                        console.log(response.data.error);
                        $scope.alertType = "danger";
                        $scope.alertMessage = "There was an error with your upload! Error: " + response.data.error;
                    }
                    $scope.uploadProposalNarrative = []; //empty array
                    $scope.uploadSupportingDocs = []; //empty array
                    $scope.populateForm(null);//refresh form so that new files show up
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
        window.location.href = "../api.php?download_file&appID="+$scope.formData.updateID+"&filename="+encodeURIComponent(filename); //redirect to download script
    };



    /*On startup*/

    // create a blank object to hold our form information
    // $scope will allow this to pass between controller and view
    $scope.formData = {};
    $scope.formData.budgetItems = []; //array of budget items
    $scope.errors = {};//all current errors
    $scope.uploadSupportingDocs = []; //array of new supporting docs
    $scope.uploadProposalNarrative = []; //array new proposal narratives

    //expense types
    $scope.options = [{ name: "Air Travel"}, 
                        { name: "Ground Travel"},
                        { name: "Hotel"},
                        { name: "Registration Fee"},
                        { name: "Per Diem"},
                        { name: "Other"}];
    
    //$scope.appStatus = null;
    /*If not creating, get app data and populate entire form*/

    if(!$scope.isCreating)
    {
        $scope.formData.updateID = app.id; //set the update id for the server
        $scope.dateSubmitted = app.dateSubmitted; //set the submission date
        $scope.appStatus = app.status; //set the application's status

        $scope.allowedFirstCycle = true; //allow selection of first cycle- only relevant if user is an admin updating.
        $scope.appFieldsDisabled = true; //disable app inputs


        //populate the form with the app data
        $scope.populateForm(app);
    }
    else //otherwise, only fill in a few fields, and alert user with warning
    {
        if($scope.allowedFirstCycle)
        {
            $scope.formData.cycleChoice = "this"; //set default cycle to this cycle
        }
        else
        {
            $scope.formData.cycleChoice = "next"; //set default cycle to next cycle
        }
        //by default, set the email field to this user's email
        $scope.formData.email = CASemail;

        //add a blank budget item
        $scope.addBudgetItem();

        alert("Please complete your application fully; applications in progress cannot be saved. Your proposal narrative and supporting documents may be uploaded later if preferred, however. Uploaded files cannot be deleted once submitted.");
    }

}]);



//Custom directive for files, originally from: https://stackoverflow.com/questions/33534497/file-upload-using-angularjs-with-php-server-script
//Needed to encrypt files as multipart/formdata, so that they are sent like other form elements. This eliminates the need to upload over SFTP separately. Modified to work with multiple files at once
//This one specifically works for supporting documents and supports many file uploads at once
higeApp.directive('readsupportingdocs', ['$parse', function ($parse) {
    return {
    restrict: 'A',
    link: function(scope, element, attrs) {
        var model = $parse(attrs.readsupportingdocs);
        var modelSetter = model.assign;

        element.bind('change', function(){
            scope.$apply(function(){
                var newFiles = Array.from(element[0].files) //save the new files in an array, not a FileList
                var allFiles = scope[attrs.readsupportingdocs].concat(newFiles); //add new files to the full array
                var allSmallEnough = true; //set to false if a file is too large

                allFiles.forEach(function (file){ //iterate over all selected files
                    if(file.size > scope.maxUploadSize){//file is too large
                        allSmallEnough = false;
                    }
                });

                if(!allSmallEnough){ //one or more files are too large
                    scope.alertType = "danger";
                    scope.alertMessage = "One or more files are too large to upload! Max file size is "+(scope.maxUploadSize/1048576)+"MB.";
                }
                else{
                    modelSetter(scope, allFiles); //set to all files
                }

                element[0].value = null; //reset value (so it doesn't try to remember previous state)
            });
        });
    }
};
}]);
//Custom directive for files, originally from: https://stackoverflow.com/questions/33534497/file-upload-using-angularjs-with-php-server-script
//Needed to encrypt files as multipart/formdata, so that they are sent like other form elements. This eliminates the need to upload over SFTP separately. Modified to work with multiple files at once
//This one specifically works for proposal narratives and is intended to support a single file at a time
higeApp.directive('readproposalnarrative', ['$parse', function ($parse) {
    return {
    restrict: 'A',
    link: function(scope, element, attrs) {
        var model = $parse(attrs.readproposalnarrative);
        var modelSetter = model.assign;

        element.bind('change', function(){
            scope.$apply(function(){
                var newFile = Array.from(element[0].files) //save the new file only
                if(newFile[0].size <= scope.maxUploadSize){//file isn't too large
                    modelSetter(scope, newFile); //set to only the newly chosen file
                }
                else{//file is too large
                    scope.alertType = "danger";
                    scope.alertMessage = "File is too large to upload! Max file size is "+(scope.maxUploadSize/1048576)+"MB.";
                }

                element[0].value = null; //reset value (so it doesn't try to remember previous state)
            });
        });
    }
};
}]);