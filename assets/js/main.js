
window.auth = function () {
    var endpoint = 'https://api.develnext.org/auth';
    var fetchAccountListeners = [];

    var getAccessToken = function () {
        return localStorage.getItem('access-token');
    };

    var setAccessToken = function (token) {
        localStorage.setItem('access-token', token);
    };

    var account = function () {
        try {
            return JSON.parse(localStorage.getItem('account'));
        } catch (err) {
            console.warn('Get Account error -> ' + err.name  + ": " + err.message);
            return null;
        }
    };


    var fetchAccount = function () {
        var token = getAccessToken();

        if (!token) {
            return null;
        }

        $.ajax({
            url: endpoint + '/account',
            method: 'GET',
            dataType: 'json',
            contentType:"application/json; charset=utf-8",
            headers: {'X-Token': token},
            success: function (data) {
                localStorage.setItem('account', JSON.stringify(data));

                $.each(fetchAccountListeners, function () {
                    this(data);
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Failed to get account, ' + textStatus);
                console.log(jqXHR);
            }
        })
    };

    var login = function (login, password, callback, errCallback) {
        $.ajax({
            url: endpoint + '/login',
            method: 'POST',
            dataType: 'json',
            contentType:"application/json; charset=utf-8",
            data: JSON.stringify({login: login, password: password}),
            success: function (data) {
                console.log('Auth success, token = ' + data.uid);
                console.log(data);
                setAccessToken(data.uid);
                fetchAccount();

                if (callback) {
                    callback(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Failed to auth, ' + textStatus);
                if (errCallback) {
                    errCallback(jqXHR);
                }
            }
        });
    };

    var addFetchAccountListener = function (callback) {
        fetchAccountListeners.push(callback);
    };

    var init = function () {
        auth.fetchAccount();

        $('.auth-component').each(function() {
            var dom = $(this);
            var submitBtn = dom.find('.submit');
            var loginField = dom.find('[name=login]');
            var passField = dom.find('[name=password]');

            submitBtn.on('click', function (e) {
                dom.find('form').trigger('submit');
                e.preventDefault();
                return false;
            });

            dom.find('form').on('submit', function () {
                submitBtn.prop('disabled', true);
                dom.find('.status').html('<div class="alert alert-info"><span class="glyphicon glyphicon-refresh spinning"></span> Подождите... </div>');

                login(loginField.val(), passField.val(), function () {
                    setTimeout(function () {
                        dom.find('.status').html('<div class="alert alert-success"><span class="glyphicon glyphicon-ok"></span> Авторизация прошла успешно.</div>');
                        submitBtn.prop('disabled', false);
                    }, 2000);
                }, function (jqXHR) {
                    setTimeout(function () {
                        if (jqXHR.responseJSON instanceof Object) {
                            var data = jqXHR.responseJSON;

                            dom.find('.status').html('<div class="alert alert-danger"><span class="glyphicon glyphicon-alert"></span> ' + data.message + '</div>');
                        }

                        submitBtn.prop('disabled', false);
                    }, 3000);
                });

                return false;
            });
        });
    };

    return {
        init: init,
        login: login,
        account: account,
        fetchAccount: fetchAccount,
        addFetchAccountListener: addFetchAccountListener
    };
}();

$(function () {
    auth.init();
});