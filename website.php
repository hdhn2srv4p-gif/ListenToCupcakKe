<?php
$albumsDir = __DIR__ . '/Albums';
$coversDir = __DIR__ . '/AllAlbumCovers';
$orderFile = __DIR__ . '/order.txt';

/* albums */
$albums = array_filter(scandir($albumsDir), fn($f) =>
  $f[0] !== '.' && is_dir("$albumsDir/$f")
);

/* ordering */
$ordered = [];
if (file_exists($orderFile)) {
  foreach (array_map('trim', file($orderFile)) as $line) {
    if (in_array($line, $albums)) $ordered[] = $line;
  }
}
foreach ($albums as $a) if (!in_array($a, $ordered)) $ordered[] = $a;
$albums = $ordered;

/* helpers */
function stripExt($f){
  return pathinfo($f, PATHINFO_FILENAME);
}

function albumCoverSet($album){
  $dir = "AllAlbumCovers/$album";
  if (is_dir($dir)) {
    return array_values(array_filter(scandir($dir), fn($f)=>$f[0]!=='.'));
  }
  return [];
}

function singleCover($album){
  foreach (scandir('AllAlbumCovers') as $f){
    if ($f[0]==='.') continue;
    if (is_file("AllAlbumCovers/$f") && pathinfo($f, PATHINFO_FILENAME)===$album)
      return "AllAlbumCovers/$f";
  }
  return null;
}

/* banner images (web paths) */
function allBannerImages($dir, $base = ''){
  $out = [];
  foreach (scandir($dir) as $f) {
    if ($f[0]==='.') continue;
    $fsPath = "$dir/$f";
    $webPath = $base ? "$base/$f" : $f;

    if (is_dir($fsPath)) {
      $out = array_merge($out, allBannerImages($fsPath, $webPath));
    } else {
      $out[] = "AllAlbumCovers/$webPath";
    }
  }
  return $out;
}

$bannerImages = allBannerImages($coversDir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>willis.teams.hosting</title>
<link rel="icon" type="image/png" href="favicon.png">

<style>
/* SF Pro + best universal fallback */
body{
  margin:0;
  background:#111;
  color:#fff;
  font-family:
    -apple-system,
    BlinkMacSystemFont,
    "SF Pro Text",
    "SF Pro Display",
    system-ui,
    Helvetica,
    Arial,
    sans-serif;
}

/* banner */
#banner{position:relative;height:350px;overflow:hidden}
#banner-text{
  position:absolute;inset:0;display:flex;
  flex-direction:column;justify-content:center;align-items:center;
  z-index:2;text-align:center;
  text-shadow:0 0 2px #000
}
#banner h1{font-size:58px;margin:0}
#banner p{font-size:26px;margin-top:10px}

.banner-row{
  position:absolute;left:0;display:flex;
  height:33.3333%;width:max-content
}
.banner-row img{
  height:100%;object-fit:cover;display:block
}

/* albums */
#albums{padding:20px}
.album{margin-bottom:15px}
.album-header{
  display:flex;align-items:center;gap:15px;
  background:#1c1c1c;padding:12px;cursor:pointer
}
.album-cover{
  width:80px;height:80px;position:relative;overflow:hidden;flex-shrink:0
}
.album-cover img{
  position:absolute;inset:0;width:100%;height:100%;
  object-fit:cover;opacity:0
}
.album-cover img.active{opacity:1}

.album-content{display:none;padding:15px;background:#151515}

/* song layout */
.song{margin-bottom:12px}
.song-name{margin-bottom:5px}
audio{width:100%}

/* mobile */
@media(max-width:768px){
  #banner{height:240px}
  #banner h1{font-size:38px}
  #banner p{font-size:18px}
}
</style>
</head>
<body>

<div id="banner">
  <div id="banner-text">
    <h1>CupcakKe is all powerful.</h1>
    <p>Listen to cupcakKe here</p>
  </div>
</div>

<div id="albums">
<?php foreach ($albums as $album): ?>
<?php $covers = albumCoverSet($album); $single = singleCover($album); ?>
<div class="album">
  <div class="album-header" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='block'?'none':'block'">
    <div class="album-cover">
      <?php if ($covers): ?>
        <?php foreach ($covers as $i=>$img): ?>
          <img src="AllAlbumCovers/<?php echo htmlspecialchars("$album/$img"); ?>" class="<?php echo $i===0?'active':''; ?>">
        <?php endforeach; ?>
      <?php elseif ($single): ?>
        <img src="<?php echo htmlspecialchars($single); ?>" class="active">
      <?php endif; ?>
    </div>
    <h2><?php echo htmlspecialchars($album); ?></h2>
  </div>

  <div class="album-content">
    <?php foreach (scandir("$albumsDir/$album") as $song): ?>
      <?php if ($song[0]==='.') continue; ?>
      <div class="song">
        <div class="song-name"><?php echo htmlspecialchars(stripExt($song)); ?></div>
        <audio controls src="Albums/<?php echo rawurlencode($album).'/'.rawurlencode($song); ?>"></audio>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<script>
/* banner grid */
const covers=<?php echo json_encode($bannerImages); ?>;
const banner=document.getElementById('banner');
const rows=3, bannerRows=[];

for(let r=0;r<rows;r++){
  const row=document.createElement('div');
  row.className='banner-row';
  row.style.top=`${r*100/rows}%`;
  banner.appendChild(row);
  bannerRows.push(row);
}

function shuffle(a){
  a=[...a];
  for(let i=a.length-1;i>0;i--){
    const j=Math.floor(Math.random()*(i+1));
    [a[i],a[j]]=[a[j],a[i]];
  }
  return a;
}

function setup(){
  const h=banner.offsetHeight/rows;
  bannerRows.forEach(row=>{
    row.innerHTML='';
    const seq=shuffle(covers);
    const needed=Math.ceil(innerWidth/h)+seq.length;
    const list=[];
    for(let i=0;i<needed;i++) list.push(seq[i%seq.length]);
    list.concat(list).forEach(src=>{
      const img=document.createElement('img');
      img.src=src;
      img.style.width=h+'px';
      row.appendChild(img);
    });
  });
}

let offset=0;
function animate(){
  offset-=1;
  bannerRows.forEach(r=>{
    const w=[...r.children].reduce((s,i)=>s+i.offsetWidth,0)/2;
    if(-offset>=w) offset=0;
    r.style.transform=`translateX(${offset}px)`;
  });
  requestAnimationFrame(animate);
}

setup();
addEventListener('resize',setup);
animate();

/* album slideshows */
document.querySelectorAll('.album-cover').forEach(c=>{
  const imgs=c.querySelectorAll('img');
  if(imgs.length<=1) return;
  let i=0;
  setInterval(()=>{
    imgs[i].classList.remove('active');
    i=(i+1)%imgs.length;
    imgs[i].classList.add('active');
  },2000);
});
</script>

</body>
</html>