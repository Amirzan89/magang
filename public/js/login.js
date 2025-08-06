const domain = window.location.protocol + '//' + window.location.hostname +":"+window.location.port;
const popup = document.querySelector('div#popup');
const redPopup = document.querySelector('div#redPopup');
const greenPopup = document.querySelector('div#greenPopup');
const inpEmail = document.getElementById('inpEmail');
const inpPassword = document.getElementById('inpPassword');
const loginForm = document.getElementById('loginForm');
const keyServer = CryptoJS.enc.Utf8.parse("A9CCF340D9A490104AC5159B8E1CBXXX");
loginForm.onsubmit = function(event){
    event.preventDefault();
    const email = inpEmail.value;
    const password = inpPassword.value;
    if (email.trim() === '') {
        showRedPopup('Email harus diisi !');
        return;
    }
    if (password.trim() === '') {
        showRedPopup('Password harus diisi !');
        return;
    }
    // showLoading();
    var xhr = new XMLHttpRequest();
    var requestBody = {
        email: inpEmail.value,
        password:inpPassword.value
    };
    xhr.open('POST',"/users/login")
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(requestBody));
    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                // closeLoading();
                var response = JSON.parse(xhr.responseText);
                console.log(response)
                // showGreenPopup(response, 'dashboard');
                // showGreenPopup(response);
            } else {
                // closeLoading();
                var response = JSON.parse(xhr.responseText);
                console.log(response)
                // showRedPopup(response);
            }
        }
    }
    return false; 
}