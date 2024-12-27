<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Bunny Motivation</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>

<body>
    <div class="container">
        <div class="image-container">
            <img class="image" src="{{ $imageUrl }}" alt="Cute Bunny">
        </div>
        <div class="quote">
            <blockquote>{{ $qoute }}</blockquote>
        </div>
    </div>
</body>

</html>
