const inpUrl = document.getElementById('url');
const inpApiKey = document.getElementById('apikey');
const inpPassword = document.getElementById('password');
const inpUniqueID = document.getElementById('uniqueid');
const inpTimestamp = document.getElementById('timestamp');
const inpDataCore = document.getElementById('datacore');
const inpDataClass = document.getElementById('datasclas');
const inpRecordPage = document.getElementById('recordsperpage');
const inpCurrentPage = document.getElementById('currentpage');
const inpCondition = document.getElementById('condition');
const inpOrder = document.getElementById('order');
const inpRecordCount = document.getElementById('recordcount');
const inpFields = document.getElementById('fields');
const inpUserID = document.getElementById('userid');
const inpGroupID = document.getElementById('groupid');
const inpBusinessID = document.getElementById('businessid');
const aesForm = document.getElementById('aesForm');
const keyServer = CryptoJS.enc.Utf8.parse("A9CCF340D9A490104AC5159B8E1CBXXX");
function handleRegister(e) {
    e.preventDefault();
    const registerForm = document.getElementById('register-form');
    const verifyForm = document.getElementById('verify-form');
    registerForm.classList.add('hidden');
    verifyForm.classList.remove('hidden');
}
function handleVerification(e) {
    e.preventDefault();
    alert("Verification success!");
}