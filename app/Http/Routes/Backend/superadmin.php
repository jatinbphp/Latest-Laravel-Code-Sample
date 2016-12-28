<?php
Route::get('marketingApproval','DashboardController@marketingApproval')
		->name('admin.marketingApproval');
		
Route::get('marketingApprovalData','DashboardController@marketingApprovalData')
	->name('admin.marketingApprovalData');	
	
Route::get('approveMarketingMail/{id}','DashboardController@approveMarketingMail')
	->name('admin.approveMarketingMail');		
	
Route::any('featureWaitlist','DashboardController@featureWaitlist')
	->name('admin.featureWaitlist');			

