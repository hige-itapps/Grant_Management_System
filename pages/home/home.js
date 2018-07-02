//init app
var higeApp = angular.module('HIGE-app', []);

/*Controller to set date inputs and list*/
higeApp.controller('homeCtrl', function($scope, $filter) {
    //get PHP init variables, also count number of views user should have
    $scope.totalViews = 0;
    $scope.totalAppsToSign = scope_totalAppsToSign;                                     if($scope.totalAppsToSign > 0){$scope.totalViews++;}
    $scope.totalSignedApps = scope_totalSignedApps;                                     if($scope.totalSignedApps > 0){$scope.totalViews++;}
    $scope.isUserAllowedToCreateApplication = scope_isUserAllowedToCreateApplication;   if($scope.isUserAllowedToCreateApplication){$scope.totalViews++;}
    $scope.totalPrevApps = scope_totalPrevApps;                                         if($scope.totalPrevApps > 0){$scope.totalViews++;}
    $scope.isUserAllowedToSeeApplications = scope_isUserAllowedToSeeApplications;       if($scope.isUserAllowedToSeeApplications){$scope.totalViews++;}
    $scope.isAdmin = scope_isAdmin;                                                     if($scope.isAdmin){$scope.totalViews++;}
    
    $scope.hasPendingApplication = scope_hasPendingApplication;
});