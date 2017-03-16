function submitFormLogin() {
  $('#loginButton').replaceWith('<button>Please wait&hellip;</button>');

  $.post('index.php', {
    login: 'form',
    uname: $('#uname').val(),
    upass: $('#upass').val()
  }, function(response) {
    window.location.replace('?');
  });
}

function submitFBLogin() {
  $('#loginButton').replaceWith('<button>Please wait&hellip;</button>');
  FB.login(function() {
    FB.getLoginStatus(function (data) {
      if (data.status !== 'connected') return;

      $.post('index.php', {
        login: 'facebook',
        token: data.authResponse.accessToken
      }, function (response) {
        window.location.replace('?');
      });
    });
  });
}

function initGLogin() {
  gapi.load('auth2', function() {
    gapi.auth2.init({
      client_id: '509882521168-i0fj4hc1qe34hvfkfbkj9nes9be69p1v.apps.googleusercontent.com',
      scope: 'profile'
    });
  })
}

function submitGLogin() {
  $('#loginButton').replaceWith('<button>Please wait&hellip;</button>');

  gapi.auth2.getAuthInstance().signIn().then(function() {
    $.post('index.php', {
      login: 'google',
      token: gapi.auth2.getAuthInstance().currentUser.get().getAuthResponse().id_token
    }, function() {
      window.location.replace('?');
    });
  });
}

function logout() {
  gapi.auth2.getAuthInstance().signOut();
  $.post('index.php', {
    logout: true,
  }, function() {
    window.location.replace('?');
  });
}
