
//import saveAs function from FileSaver.js library so that user can download as excel sheet; requires ES6 syntax to be enabled by browser
import { saveAs } from '../include/FileSaver.js-master/src/FileSaver.js';

//init app
var higeApp = angular.module('HIGE-app', []);

/*Controller to set date inputs and list*/
higeApp.controller('listCtrl', function($scope, $filter) {
    //get PHP init variables
    $scope.applications = scope_applications;
    $scope.appCycles = scope_appCycles;
    $scope.isAllowedToSeeApplications = scope_isAllowedToSeeApplications;




    /*support browsers that don't have built-in datepicker input functionality (looking at you Safari...)
    source: http://www.javascriptkit.com/javatutors/createelementcheck2.shtml */
    var datefield=document.createElement("input");
    datefield.setAttribute("type", "date");
    if (datefield.type!="date"){ //if browser doesn't support input type="date", initialize date picker widget:
        jQuery(function($){ //on document.ready
            $('#filterDateFrom').datepicker({ dateFormat: 'yy-mm-dd' });
            $('#filterDateTo').datepicker({ dateFormat: 'yy-mm-dd' });
        })

        //Custom directive for JQuery Datepicker field (only necessary for when the browser doesn't natively support it, like with Safari)
        //Source: https://stackoverflow.com/questions/18144142/jquery-ui-datepicker-with-angularjs
        higeApp.directive("datepicker", function () {
            return {
                restrict: "A",
                require: "ngModel",
                link: function (scope, element, attrs, ngModelCtrl) {
                    element.datepicker({
                        dateFormat: 'yy-mm-dd',
                        onSelect: function (date) {
                            //dates require a bit of extra work to convert properly! Javascript offsets the dates based on timezones, and one way to combat that is by replacing hyphens with slashes (don't ask me why)
                            var saveDate = new Date(date.replace(/-/g, '\/'));

                            var ngModelName = this.attributes['ng-model'].value;

                            // if value for the specified ngModel is a property of 
                            // another object on the scope
                            if (ngModelName.indexOf(".") != -1) {
                                var objAttributes = ngModelName.split(".");
                                var lastAttribute = objAttributes.pop();
                                var partialObjString = objAttributes.join(".");
                                var partialObj = eval("scope." + partialObjString);

                                partialObj[lastAttribute] = saveDate;
                            }
                            // if value for the specified ngModel is directly on the scope
                            else {
                                scope[ngModelName] = saveDate;
                            }
                            scope.$apply();
                        }
                    });
                }
            }
        });
    }



    
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