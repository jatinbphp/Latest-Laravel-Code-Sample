<?php
Route::get('dashboard', 'DashboardController@index')
->name('admin.dashboard');

Route::any('restaurant_add', 'DashboardController@restaurant_add')
->name('admin.restaurant_add');

Route::any('manage_waitlist', 'DashboardController@manage_waitlist')
->name('admin.manage_waitlist');

Route::post('save_waitlistdata', 'DashboardController@save_waitlistdata')
->name('admin.save_waitlistdata');

Route::post('waitlist_seated', 'DashboardController@waitlist_seated')
->name('admin.waitlist_seated');

Route::get('get_waitlist', 'DashboardController@get_waitlist')
->name('admin.get_waitlist');

Route::post('waitlist_incomplete', 'DashboardController@waitlist_incomplete')
->name('admin.waitlist_incomplete');

Route::post('waitlist_note_present', 'DashboardController@waitlist_note_present')
->name('admin.waitlist_note_present');

Route::post('waitlist_delete', 'DashboardController@waitlist_delete')
->name('admin.waitlist_delete');

Route::post('waitlist_change_time', 'DashboardController@waitlist_change_time')
->name('admin.waitlist_change_time');

Route::get('get_waitlist_history', 'DashboardController@get_waitlist_history')
->name('admin.get_waitlist_history');

Route::get('checkin', 'DashboardController@checkin')
->name('admin.checkin');

Route::post('getguestdata', 'DashboardController@getguestdata')
->name('admin.getguestdata');

Route::get('current_serv_time', 'DashboardController@current_serv_time')
->name('admin.current_serv_time');

Route::get('settings', 'DashboardController@settings')
->name('admin.settings');

Route::post('save_table_pref_restaurant', 'DashboardController@save_table_pref_restaurant')
->name('admin.save_table_pref_restaurant');

Route::post('remove_table_pref_restaurant', 'DashboardController@remove_table_pref_restaurant')
->name('admin.remove_table_pref_restaurant');

Route::post('timezone_update', 'DashboardController@timezone_update')
->name('admin.timezone_update');

Route::post('resettime_update', 'DashboardController@resettime_update')
->name('admin.resettime_update');

Route::any('profile', 'DashboardController@profile')
->name('admin.profile');

Route::group([
    'middleware' => 'access.routeNeedsPermission:manage-users',
], function () {

    Route::get('users', 'DashboardController@users')
        ->name('admin.users');
});

Route::get('subscription', 'DashboardController@subscription')
    ->name('admin.subscription');

Route::get('forum', 'DashboardController@forum')
    ->name('admin.forum');

Route::get('analytics', 'DashboardController@analytics')
    ->name('admin.analytics');

Route::get('knowledgebase', 'DashboardController@knowledgebase')
    ->name('admin.knowledgebase');

Route::get('managereview', 'DashboardController@manageReview')
    ->name('admin.managereview');

Route::post('update_profile_image', 'DashboardController@update_profile_image')
    ->name('admin.update_profile_image');

Route::post('save_reff_type_setting', 'DashboardController@save_reff_type_setting')
    ->name('admin.save_reff_type_setting');

Route::post('save_redeem', 'DashboardController@save_redeem')
    ->name('admin.save_redeem');

Route::post('delete_redeem', 'DashboardController@delete_redeem')
    ->name('admin.delete_redeem');

Route::post('save_vip_qualification', 'DashboardController@save_vip_qualification')
    ->name('admin.save_vip_qualification');

Route::post('saveVipQualCondition', 'DashboardController@saveVipQualCondition')
    ->name('admin.saveVipQualCondition');

Route::post('delete_condition', 'DashboardController@delete_condition')
    ->name('admin.delete_condition');

Route::post('update_default_visit', 'DashboardController@update_default_visit')
    ->name('admin.update_default_visit');

Route::post('update_default_amt_spent', 'DashboardController@update_default_amt_spent')
    ->name('admin.update_default_amt_spent');

Route::get('selfCheckin', 'DashboardController@selfCheckin')
    ->name('admin.selfCheckin');

Route::post('selfCheckinAjax', 'DashboardController@selfCheckinAjax')
    ->name('admin.selfCheckinAjax');

Route::post('selfCheckinAddAjax', 'DashboardController@selfCheckinAddAjax')
    ->name('admin.selfCheckinAddAjax');

Route::post('saveCheckinSettings', 'DashboardController@saveCheckinSettings')
    ->name('admin.saveCheckinSettings');

Route::post('checkinConfirmAjax', 'DashboardController@checkinConfirmAjax')
    ->name('admin.checkinConfirmAjax');

Route::post('checkinConfirmAjaxUpdate', 'DashboardController@checkinConfirmAjaxUpdate')
    ->name('admin.checkinConfirmAjaxUpdate');

Route::get('staffCheckin', 'DashboardController@staffCheckin')
    ->name('admin.staffCheckin');

Route::post('staffCheckinSeachAjax', 'DashboardController@staffCheckinSeachAjax')
    ->name('admin.staffCheckinSeachAjax');

Route::post('staffCheckinSave', 'DashboardController@staffCheckinSave')
    ->name('admin.staffCheckinSave');

Route::post('addPointsfromStaff', 'DashboardController@addPointsfromStaff')
    ->name('admin.addPointsfromStaff');

Route::post('staffReferral', 'DashboardController@staffReferral')
    ->name('admin.staffReferral');

Route::get('viewdatabase', 'DashboardController@viewDatabase')
    ->name('admin.viewdatabase');

Route::get('databasefetch', 'DashboardController@databasefetch')
    ->name('admin.databasefetch');

Route::post('LastVisitCounter', 'DashboardController@LastVisitCounter')
    ->name('admin.LastVisitCounter');

Route::post('AmountSpentTotal', 'DashboardController@AmountSpentTotal')
    ->name('admin.AmountSpentTotal');

Route::post('UpdateMergeRecordStatus', 'DashboardController@UpdateMergeRecordStatus')
    ->name('admin.UpdateMergeRecordStatus');

Route::get('exportCsvDatabaseWaitlist', 'DashboardController@exportCsvDatabaseWaitlist')
    ->name('admin.exportCsvDatabaseWaitlist');

Route::post('importDatabaseCsv', 'DashboardController@importDatabaseCsv')
    ->name('admin.importDatabaseCsv');

Route::post('addNewformData', 'DashboardController@addNewformData')
    ->name('admin.addNewformData');

Route::post('editDatabaseDetail', 'DashboardController@editDatabaseDetail')
    ->name('admin.editDatabaseDetail');

Route::post('saveDatabaseDetail', 'DashboardController@saveDatabaseDetail')
    ->name('admin.saveDatabaseDetail');

Route::post('redeemPointStaff', 'DashboardController@redeemPointStaff')
    ->name('admin.redeemPointStaff');

Route::get('marketing/{id?}', 'DashboardController@marketing')
    ->name('admin.marketing');

Route::get('customerFetch', 'DashboardController@customerFetch')
    ->name('admin.customerFetch');

Route::post('fetchCustomerEmail', 'DashboardController@fetchCustomerEmail')
    ->name('admin.fetchCustomerEmail');

Route::post('marketingMail/{id?}', 'DashboardController@marketingMail')
    ->name('admin.marketingMail');

Route::post('marketingSms/{id?}', 'DashboardController@marketingSms')
    ->name('admin.marketingSms');

Route::post('smsStore/{id?}', 'DashboardController@smsStore')
    ->name('admin.smsStore');

Route::post('saveSmtpCredential', 'DashboardController@saveSmtpCredential')
    ->name('admin.saveSmtpCredential');

Route::get('networkMarketing/{id?}', 'DashboardController@networkMarketing')
    ->name('admin.networkMarketing');

Route::post('SaveMarketingMail', 'DashboardController@SaveMarketingMail')
    ->name('admin.SaveMarketingMail');

Route::post('removeSavedMarketingMail', 'DashboardController@removeSavedMarketingMail')
    ->name('admin.removeSavedMarketingMail');

Route::post('ApprovalSendMarketingMail', 'DashboardController@ApprovalSendMarketingMail')
    ->name('admin.ApprovalSendMarketingMail');

Route::post('SaveMarketingSMS', 'DashboardController@SaveMarketingSMS')
    ->name('admin.SaveMarketingSMS');

Route::post('fetchMarketingSMSData', 'DashboardController@fetchMarketingSMSData')
    ->name('admin.fetchMarketingSMSData');

Route::post('fetchMarketingSmsCost', 'DashboardController@fetchMarketingSmsCost')
    ->name('admin.fetchMarketingSmsCost');

Route::post('removeScheduledMarketingMail', 'DashboardController@removeScheduledMarketingMail')
    ->name('admin.removeScheduledMarketingMail');

//=========@@ Azim Routes @@===========

Route::get('affiliateMarketing', 'DashboardController@affiliateMarketing')
    ->name('admin.affiliateMarketing');

Route::get('saveCommissionRate', 'DashboardController@saveCommissionRate')
    ->name('admin.saveCommissionRate');

Route::get('loadAddAffiliateModal', 'DashboardController@loadAddAffiliateModal')
    ->name('admin.loadAddAffiliateModal');

Route::post('saveAffiliate', 'DashboardController@saveAffiliate')
    ->name('admin.saveAffiliate');

Route::get('saveEditAffiliate', 'DashboardController@saveEditAffiliate')
    ->name('admin.saveEditAffiliate');

Route::get('deactiveAffiliate/{id}', 'DashboardController@deactiveAffiliate')
    ->name('admin.deactiveAffiliate');

Route::get('activeAffiliate/{id}', 'DashboardController@activeAffiliate')
    ->name('admin.activeAffiliate');

Route::get('getMonthWisePayment', 'DashboardController@getMonthWisePayment')
    ->name('admin.getMonthWisePayment');

Route::post('removeDraft', 'DashboardController@removeDraft')
    ->name('admin.removeDraft');

Route::post('removeDraftSms', 'DashboardController@removeDraftSms')
    ->name('admin.removeDraftSms');

Route::post('fetchSmsData', 'DashboardController@fetchSmsData')
    ->name('admin.fetchSmsData');

Route::post('fetchMarketingMailCost', 'DashboardController@fetchMarketingMailCost')
    ->name('admin.fetchMarketingMailCost');

Route::post('sendNetworkMarketingMail', 'DashboardController@sendNetworkMarketingMail')
    ->name('admin.sendNetworkMarketingMail');

Route::post('sendNetworkMarketingSms', 'DashboardController@sendNetworkMarketingSms')
    ->name('admin.sendNetworkMarketingSms');

Route::post('sendScheduleMarketingMail', 'DashboardController@sendScheduleMarketingMail')
    ->name('admin.sendScheduleMarketingMail');

Route::post('sendScheduleMarketingSMS', 'DashboardController@sendScheduleMarketingSMS')
    ->name('admin.sendScheduleMarketingSMS');

Route::post('fetchEmailContent', 'DashboardController@fetchEmailContent')
    ->name('admin.fetchEmailContent');

Route::post('notifyFirst', 'DashboardController@notifyFirst')
    ->name('admin.notifyFirst');

Route::get('test', 'DashboardController@test')
    ->name('admin.test');

//=========@@ Azim Routes @@===========

//-------Routs for financials by Neyamul-------
Route::get('dashboard', 'DashboardController@index')->name('admin.dashboard');

Route::get('financials', 'DashboardController@financial')->name('admin.financials');

Route::get('addFinancial', 'DashboardController@financialModal')->name('admin.addFinancial');

Route::post('saveFinancial', 'DashboardController@saveFinancial')->name('admin.saveFinancial');

Route::get('addAdditionalServicesModal', 'DashboardController@addAdditionalServicesModal')->name('admin.addAdditionalServicesModal');

Route::post('saveAdditionalService', 'DashboardController@saveAdditionalService')->name('admin.saveAdditionalService');

Route::get('saveEditRevenues', 'DashboardController@saveEditRevenue')->name('admin.saveEditRevenues');

Route::get('inactiveRevanue', 'DashboardController@inactiveRevanues')->name('admin.inactiveRevanue');
// ----------End neyamuls code----------
