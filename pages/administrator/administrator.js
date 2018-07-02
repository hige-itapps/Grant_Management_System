var myApp = angular.module('HIGE-app', []);

/*App Controller*/
myApp.controller('adminCtrl', function($scope, $http) {
    //get PHP init variables
    $scope.administrators = scope_administrators;
    $scope.applicationApprovers = scope_applicationApprovers
    $scope.committee = scope_committee
    $scope.followUpApprovers = scope_followUpApprovers;


    /*Functions*/

    //remove the alert from the page
    $scope.removeAlert = function(){
        $scope.alertMessage = null;
    }




    /*For Adding People*/

    //add an admin
    $scope.addAdmin = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/add_admin.php',
            data    : $.param({broncoNetID: $scope.addAdminID, name: $scope.addAdminName}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getAdmins(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! " + $scope.addAdminName + " has been added as an admin.";
                    $scope.addAdminName = ""; //reset inputs
                    $scope.addAdminID = "";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: " + $scope.addAdminName + " was not added as an admin.";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to add the admin! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //add a committee member
    $scope.addCommittee = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/add_committee_member.php',
            data    : $.param({broncoNetID: $scope.addCommitteeID, name: $scope.addCommitteeName}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getCommittee(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! " + $scope.addCommitteeName + " has been added as a committee member.";
                    $scope.addCommitteeName = ""; //reset inputs
                    $scope.addCommitteeID = "";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: " + $scope.addCommitteeName + " was not added as a committee member.";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to add the committee member! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //add an application approver
    $scope.addApplicationApprover = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/add_application_approver.php',
            data    : $.param({broncoNetID: $scope.addApplicationApproverID, name: $scope.addApplicationApproverName}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getApplicationApprovers(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! " + $scope.addApplicationApproverName + " has been added as an application approver.";
                    $scope.addApplicationApproverName = ""; //reset inputs
                    $scope.addApplicationApproverID = "";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: " + $scope.addApplicationApproverName + " was not added as an application approver.";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to add the application approver! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //add a follow up report approver
    $scope.addFollowUpApprover = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/add_follow_up_approver.php',
            data    : $.param({broncoNetID: $scope.addFollowUpApproverID, name: $scope.addFollowUpApproverName}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getFollowUpApprovers(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! " + $scope.addFollowUpApproverName + " has been added as a follow up report approver.";
                    $scope.addFollowUpApproverName = ""; //reset inputs
                    $scope.addFollowUpApproverID = "";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: " + $scope.addFollowUpApproverName + " was not added as a follow up report approver.";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to add the follow up report approver! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };





    /*For Removing People*/

    //remove an admin
    $scope.removeAdmin = function(id){
        $http({
            method  : 'POST',
            url     : '/../../ajax/remove_admin.php',
            data    : $.param({broncoNetID: id}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getAdmins(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! the admin was removed.";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: unable to remove the admin!";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to remove the admin! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //remove a committee member
    $scope.removeCommittee = function(id){
        $http({
            method  : 'POST',
            url     : '/../../ajax/remove_committee_member.php',
            data    : $.param({broncoNetID: id}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getCommittee(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! the committee member was removed.";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: unable to remove the committee member!";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to remove the committee member! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //remove an application approver
    $scope.removeApplicationApprover = function(id){
        $http({
            method  : 'POST',
            url     : '/../../ajax/remove_application_approver.php',
            data    : $.param({broncoNetID: id}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getApplicationApprovers(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! the application approver was removed.";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: unable to remove the application approver!";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to remove the application approver! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //remove a follow up report approver
    $scope.removeFollowUpApprover = function(id){
        $http({
            method  : 'POST',
            url     : '/../../ajax/remove_follow_up_approver.php',
            data    : $.param({broncoNetID: id}),  // pass in data as strings
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                response.data = response.data.trim();//remove blankspace around data
                if(response.data === "true")//updated
                {
                    $scope.getFollowUpApprovers(); //refresh the form again
                    $scope.alertType = "success";
                    $scope.alertMessage = "Success! the follow up report approver was removed.";
                }
                else//didn't update
                {
                    $scope.alertType = "warning";
                    $scope.alertMessage = "Warning: unable to remove the follow up report approver!";
                }
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to remove the follow up report approver! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };





    /*For Refreshing Lists*/

    //refresh the admin list by getting the most up-to-date list from the database
    $scope.getAdmins = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/get_admins.php'
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                $scope.administrators = response.data;
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to get the admins list! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //refresh the committee list by getting the most up-to-date list from the database
    $scope.getCommittee = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/get_committee_members.php'
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                $scope.committee = response.data;
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to get the committee members list! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //refresh the application approvers list by getting the most up-to-date list from the database
    $scope.getApplicationApprovers = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/get_application_approvers.php'
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                $scope.applicationApprovers = response.data;
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to get the application approvers list! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

    //refresh the follow up report approvers list by getting the most up-to-date list from the database
    $scope.getFollowUpApprovers = function(){
        $http({
            method  : 'POST',
            url     : '/../../ajax/get_follow_up_approvers.php'
        })
        .then(function (response) {
            console.log(response, 'res');
            if(typeof response.data.error === 'undefined') //ran function as expected
            {
                $scope.followUpApprovers = response.data;
            }
            else //failure!
            {
                console.log(response.data.error);
                $scope.alertType = "danger";
                $scope.alertMessage = "There was an error when trying to get the follow up approvers list! Error: " + response.data.error;
            }
        },function (error){
            console.log(error, 'can not get data.');
        });
    };

});




