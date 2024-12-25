<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Inspiration</title>
    <link rel="stylesheet" href="styles.css">
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
