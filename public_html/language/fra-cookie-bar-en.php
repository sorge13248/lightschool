<?php
// @TODO convert everything in a JSON file so server is less busy in useless job

if (isset($_GET["header"]) && $_GET["header"] === "json") header('Content-type:application/json;charset=utf-8');

$strings = [
    "cookie-bar" => "Cookie policy",
    "cookie-bar-last-edit" => "Last update: 22/09/2018 13:00",
    "cookie-bar-description" => "We use cookies to know which language you want to use, for keeping track you agreed with this policy (and so not showing the banner everytime you enter this site), to keep open your account session on our services (ex: LightSchool) and some third parties services may use cookies.<br/>If you're not ok with this, you can deactivate cookies for this website from your browser's settings; keep in mind that deactivating cookies might cause the site to do not work properly (or not to work at all).<br/>We may change this policy without any warning. You're supposed to check this policy periodically and by using this site, you agree to any modifications we may make.",
    "cookie-bar-dialog" => "<br/><br/>In order to show this message, I already set a cookie and guess what? Your device didn't blow up! This should make you comfortable with cookies because they're not evil.<br/><br/>Now you can choose: you may accept this policy and navigate this site, or you may delete every cookie we might have saved (delete them manually, just to be sure) and exit this site (I'll take you to Google).<br/><small style='margin-top: 10px; display: inline-block'>N.B.: You will be able to read this policy again by visiting the right page.</small>",
    "cookie-bar-exit" => "Delete cookies and exit",
    "cookie-bar-accept" => "Accept",
    "cookie-show-message-again" => "Show the banner again",
];
echo(json_encode($strings));