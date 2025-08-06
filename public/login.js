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
document.getElementById("loginForm").addEventListener("submit", function(e) {
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const errorDiv = document.getElementById("error");

    if (!email || !password) {
        e.preventDefault();
        errorDiv.textContent = "Semua field harus diisi!";
        errorDiv.style.display = "block";
        return;
    }

    errorDiv.style.display = "none";
});