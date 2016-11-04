var _ss = _ss || [];
_ss.push(['_setDomain', ss_account_settings.domain]);
_ss.push(['_setAccount', ss_account_settings.account]);

var subDomain = ss_account_settings.domain.match(/https?:\/\/([^.]+)./)[1];

(function() {
    var ss = document.createElement('script');
    ss.type = 'text/javascript'; ss.async = true;

    ss.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + subDomain + '.marketingautomation.services/client/ss.js?ver=1.1.1';
    var scr = document.getElementsByTagName('script')[0];
    scr.parentNode.insertBefore(ss, scr);
})();

