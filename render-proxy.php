<?php
$renderBase = "https://serenayogaa.onrender.com";

/* -------------------------------------------------
   ASSET MODE — DIRECT FILE PROXY (FASTEST)
-------------------------------------------------- */
if (isset($_GET["asset"])) {

  $asset = ltrim($_GET["asset"], "/");
  $assetUrl = $renderBase . "/" . $asset;

  $ch = curl_init($assetUrl);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HEADER => false,
    CURLOPT_HTTPHEADER => ["User-Agent: MainSiteProxy"]
  ]);

  $data = curl_exec($ch);
  $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($code !== 200 || !$data) {
    http_response_code(404);
    exit;
  }

  if ($type) header("Content-Type: ".$type);
  header("Cache-Control: public, max-age=604800");
  echo $data;
  exit;
}

/* -------------------------------------------------
   HTML MODE — FETCH FULL PAGE
-------------------------------------------------- */
$ch = curl_init($renderBase . "/");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 20,
  CURLOPT_HEADER => false,
  CURLOPT_HTTPHEADER => ["User-Agent: MainSiteProxy"]
]);

$html = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$html) {
  http_response_code(403);
  exit;
}

/* -------------------------------------------------
   FIX ALL RELATIVE LINKS (THE MAGIC PART)
-------------------------------------------------- */
$html = preg_replace_callback(
  '/\b(src|href)=["\'](.*?)["\']/i',
  function ($m) {
    $attr = $m[1];
    $url  = $m[2];

    // Skip absolute URLs / anchors / data URIs
    if (
      preg_match('#^(https?:)?//#', $url) ||
      strpos($url, '#') === 0 ||
      strpos($url, 'data:') === 0
    ) {
      return $m[0];
    }

    return $attr . '="/render-proxy.php?asset=' . urlencode($url) . '"';
  },
  $html
);

/* -------------------------------------------------
   EXTRACT HEAD + BODY EXACTLY (NO LOSS)
-------------------------------------------------- */
preg_match('/<head[^>]*>(.*?)<\/head>/is', $html, $head);
preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $body);

/* -------------------------------------------------
   RETURN JSON TO FRONTEND
-------------------------------------------------- */
header("Content-Type: application/json");
echo json_encode([
  "head" => $head[1] ?? "",
  "body" => $body[1] ?? ""
]);
?>