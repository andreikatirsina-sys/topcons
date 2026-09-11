# download-images.ps1
# Descarca toate pozele site-ului in folderul assets/, o singura data.
#
# Rulare: click dreapta pe fisier -> "Run with PowerShell"
# SAU din PowerShell, in folderul site-ului: .\download-images.ps1

$ErrorActionPreference = "Stop"

$folder = "assets\images"
if (!(Test-Path $folder)) {
    New-Item -ItemType Directory -Path $folder -Force | Out-Null
}

$images = @{
    "hero.jpg"                        = "https://images.unsplash.com/photo-1593786267440-550458cc882a?q=80&w=1600&auto=format&fit=crop"
    "servicii-01-constructii.jpg"     = "https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?q=80&w=800&auto=format&fit=crop"
    "servicii-02-reparatii.jpg"       = "https://images.unsplash.com/photo-1643509867448-57001e0c333d?q=80&w=800&auto=format&fit=crop"
    "servicii-03-renovari.jpg"        = "https://images.unsplash.com/photo-1556156653-e5a7c69cc263?q=80&w=800&auto=format&fit=crop"
    "servicii-04-mesteri.jpg"         = "https://images.unsplash.com/photo-1587582423116-ec07293f0395?q=80&w=1000&auto=format&fit=crop"
    "servicii-05-acoperisuri.jpg"     = "https://images.unsplash.com/photo-1635424824849-1b09bdcc55b1?q=80&w=800&auto=format&fit=crop"
    "servicii-06-fatade.jpg"          = "https://images.unsplash.com/photo-1565145211217-d0f630de0004?q=80&w=800&auto=format&fit=crop"
    "why-santier.jpg"                 = "https://images.unsplash.com/photo-1508450859948-4e04fabaa4ea?q=80&w=900&auto=format&fit=crop"
    "galerie-01-casa.jpg"             = "https://images.unsplash.com/photo-1721815693498-cc28507c0ba2?q=80&w=1000&auto=format&fit=crop"
    "galerie-02-acoperis.jpg"         = "https://images.unsplash.com/photo-1635424709961-f3a150459ad4?q=80&w=700&auto=format&fit=crop"
    "galerie-03-fatada.jpg"           = "https://images.unsplash.com/photo-1580747244280-08248054e21a?q=80&w=700&auto=format&fit=crop"
    "galerie-04-renovare.jpg"         = "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?q=80&w=700&auto=format&fit=crop"
}

foreach ($file in $images.Keys) {
    $url = $images[$file]
    $dest = Join-Path $folder $file
    Write-Host "Descarc $file ..."
    Invoke-WebRequest -Uri $url -OutFile $dest
}

Write-Host ""
Write-Host "Gata! Toate pozele sunt in folderul assets/."
