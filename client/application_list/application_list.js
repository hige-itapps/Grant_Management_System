
//import saveAs function from FileSaver.js library so that user can download as excel sheet; requires ES6 syntax to be enabled by browser
import { saveAs } from '/../include/FileSaver.js-master/src/FileSaver.js';

//init app
var higeApp = angular.module('HIGE-app', []);

/*Controller to set date inputs and list*/
higeApp.controller('listCtrl', function($scope, $filter) {
    //get PHP init variables
    $scope.applications = scope_applications;
    $scope.appCycles = scope_appCycles;
    $scope.isAllowedToSeeApplications = scope_isAllowedToSeeApplications;

    
    /*Functions*/

    $scope.exportExcelSheet = function () {
        //console.log($scope.filteredApplications); //the current selection of applications to be exported
        var exportList = "Application ID\tName\tDepartment\tPrevious Approved Cycles\tPurpose(s)\tDestination\tProject Title\tTravel From\tTravel To\tActivity From\tActivity To\tTotal Budget\tRequested Award\tAmount Awarded\tComments\n"; //initialize final export data, also set the header line
        
        $scope.filteredApplications.forEach(function(app){ //append each row onto the exportList
            exportList += app['id'] + "\t";
            exportList += app['name'] + "\t";
            exportList += app['department'] + "\t";
            exportList += (app['pastApprovedCycles'] != null ? app['pastApprovedCycles'].join(", ") : "") + "\t"; //turn into comma separated list, or empty string if null
            
            var purposesArray = new Array(); //array of purposes as strings
            if(app['purpose1'] === 1) {purposesArray.push("Research");}
            if(app['purpose2'] === 1) {purposesArray.push("Conference");}
            if(app['purpose3'] === 1) {purposesArray.push("Creative Activity");}
            if(app['purpose4'] !== '') {purposesArray.push("Other");}
            exportList += purposesArray.join(", ") + "\t"; //turn into comma separated list

            exportList += app['destination'] + "\t";
            exportList += app['title'] + "\t";
            exportList += app['travelFrom'] + "\t";
            exportList += app['travelTo'] + "\t";
            exportList += app['activityFrom'] + "\t";
            exportList += app['activityTo'] + "\t";

            var totalBudget = 0; //the total budget for this app
            app['budget'].forEach(function(budgetItem){
                totalBudget += budgetItem[3]*100; //add this cost to the total (AS CENTS TO AVOID FLOATING POINT ISSUES)
            });
            var totalBudgetString = totalBudget.toString();//format as string
            exportList += "$" + totalBudgetString.substr(0, totalBudgetString.length-2) + "." + totalBudgetString.substr(totalBudgetString.length-2) + "\t"; //splice in a decimal point in the correct spot

            exportList += "$" + app['amountRequested'] + "\t";
            exportList += (app['amountAwarded'] != null ? "$"+app['amountAwarded'] : "") + "\t";//amount awarded; if something has already been awarded, mention it, but otherwise leave it blank
            exportList += "\n"; //empty comments row
        });
        
        //console.log(exportList);

        var blob = new Blob([exportList], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
        });
        saveAs(blob, "SummarySheet.xls");
    };
});

/*Custom filter used to filter within a date range*/
higeApp.filter("dateFilter", function() {
    //alert("running date filter");
    return function(items, dateFrom, dateTo) {
        var result = [];  

        for (var i=0; i<items.length; i++){
            var dateSubmittedub = new Date(items[i].dateSubmitted);
            if(dateFrom == null && dateTo == null)		{result.push(items[i]);} //if neither filter date is defined
            else if(dateFrom != null && dateTo != null)	{if (dateSubmittedub >= dateFrom && dateSubmittedub <= dateTo){result.push(items[i]);}} //if both filter dates are defined
            else if(dateFrom != null) 					{if (dateSubmittedub >= dateFrom){result.push(items[i]);}} //if only from date is defined
            else if(dateTo != null)						{if (dateSubmittedub <= dateTo){result.push(items[i]);}} //if only to date to is defined
        }
        
        return result;
    };
});