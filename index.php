<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aleinah's Private Resort</title>
    <link rel="stylesheet" href="index.css">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>

<header class="header rates-header">
    <a href="index.php" class="logo" id="logo-link" style="text-decoration:none;">
        <img src="images/aleinahslogo.png" alt="Aleinah's Private Resort Logo">
        <span class="logo-text">Aleinah's Private Resort</span>
    </a>
    <nav class="rates-nav-menu">
        <a href="index.php" class="active">Home</a>
        <a href="amenities.php">Amenities</a>
        <a href="rates.php">Rates</a>
        <a href="location.php">Location</a>
        <a href="contact.php">Contact Us</a>
    </nav>
</header>

<div class="container main-container fade-in">
    <div class="left-section">
        <h1 class="calligraphy">
            <span id="slogan-text">Your Dream Staycation</span>
        </h1>
        <h2 class="calligraphy slogan-line-2">
            <span id="slogan-text-2">Starts Here...</span>
        </h2>
        <p>Experience a luxurious private escape designed for ultimate relaxation.</p>
        <a href="paymongo-payment-method/index.php?package=Day%20Tour&price=6000" class="btn modern-btn">Book Your Stay</a>
    </div>
    <div class="right-section hero-section">
        <span class="hero-overlay"></span>
    </div>
</div>

<script>
    // === Background slideshow (auto-change) ===
    const images = [
        "images/landingpagebg.jpg",
        "images/pool.jpg",
        "images/pool1.jpg",
        "images/pool2.jpg",
        "images/house.jpg",
    ];
    let index = 0;
    const rightSection = document.querySelector(".right-section");
    function changeBackground() {
        // Apply opacity transition to make the change smoother (CSS transition on background-image is used)
        rightSection.style.backgroundImage = `url('${images[index]}')`;
        index = (index + 1) % images.length;
    }
    changeBackground();
    setInterval(changeBackground, 5000);

    // === Typing animation for slogan ===
    const sloganTextElement = document.getElementById('slogan-text');
    const sloganText2Element = document.getElementById('slogan-text-2');
    const firstText = "Your Dream Staycation";
    const secondText = "Starts Here...";
    const typingSpeed = 50;

    // Clear initial text to prepare for typing animation
    sloganTextElement.textContent = "";
    sloganText2Element.textContent = "";

    function typeText(element, text, callback) {
        let charIndex = 0;
        function type() {
            if (charIndex < text.length) {
                element.textContent += text.charAt(charIndex);
                charIndex++;
                setTimeout(type, typingSpeed);
            } else if (callback) {
                callback();
            }
        }
        type();
    }

    // Start the typing animation sequence
    function startTyping() {
        typeText(sloganTextElement, firstText, () => {
            // After the first text is fully typed, start the second one
            setTimeout(() => {
                typeText(sloganText2Element, secondText);
            }, 500); // 500ms delay between the two phrases
        });
    }

    // Add .fade-in class to start the main content animation
    document.querySelector('.main-container').classList.add('fade-in');
    
    // Start typing animation on page load
    window.onload = startTyping;

    // === Admin Login (Long press) ===
    const logo = document.getElementById('logo-link');
    const loginPageUrl = 'login/index.php';
    const pressDuration = 5000; // 5 seconds
    let pressTimer;

    function startLogoPress(e) {
        e.preventDefault();
        pressTimer = setTimeout(() => {
            window.location.href = loginPageUrl;
        }, pressDuration);
    }

    function cancelLogoPress() {
        clearTimeout(pressTimer);
    }

    logo.addEventListener('mousedown', startLogoPress);
    logo.addEventListener('mouseup', cancelLogoPress);
    logo.addEventListener('mouseleave', cancelLogoPress);
    logo.addEventListener('touchstart', startLogoPress, { passive: false });
    logo.addEventListener('touchend', cancelLogoPress);
    logo.addEventListener('touchcancel', cancelLogoPress);
</script>

</body>
</html>