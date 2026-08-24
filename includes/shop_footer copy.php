<?php
/**
 * SAMRIDHI AGRO - Shop Footer Include
 *
 * This file contains the common footer structure for all shop pages.
 *
 * @package SamridhiAgro
 * @subpackage Includes
 * @version 1.0.0
 */
?>

<!-- =========================================================
     SWEETALERT2 - COMMON LIBRARY
     सभी shop pages पर available रहेगा
========================================================= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo JS_URL; ?>main.js"></script>


<?php if (!empty($paymentSuccess)): ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================================================
    // PAYMENT SUCCESS DATA
    // =========================================================

    const paymentData = <?php echo json_encode(
        $paymentSuccess,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ); ?>;

    const amount = Number(paymentData.amount || 0);
    const receiverType = paymentData.receiver_type || 'admin';
    const receiverName = paymentData.receiver_name || 'Admin';
    const orderNumber = paymentData.order_number || '';

    // =========================================================
    // FORMAT AMOUNT
    // =========================================================

    const formattedAmount = amount.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });


    // =========================================================
    // HINDI NUMBER CONVERTER
    // =========================================================

    function numberToHindi(num) {

        num = Math.floor(Number(num));

        if (num === 0) {
            return 'शून्य';
        }

        const ones = [
            '',
            'एक',
            'दो',
            'तीन',
            'चार',
            'पाँच',
            'छह',
            'सात',
            'आठ',
            'नौ',
            'दस',
            'ग्यारह',
            'बारह',
            'तेरह',
            'चौदह',
            'पंद्रह',
            'सोलह',
            'सत्रह',
            'अठारह',
            'उन्नीस',
            'बीस',
            'इक्कीस',
            'बाईस',
            'तेईस',
            'चौबीस',
            'पच्चीस',
            'छब्बीस',
            'सत्ताईस',
            'अट्ठाईस',
            'उनतीस',
            'तीस',
            'इकतीस',
            'बत्तीस',
            'तैंतीस',
            'चौंतीस',
            'पैंतीस',
            'छत्तीस',
            'सैंतीस',
            'अड़तीस',
            'उनतालीस',
            'चालीस',
            'इकतालीस',
            'बयालीस',
            'तैंतालीस',
            'चवालीस',
            'पैंतालीस',
            'छियालीस',
            'सैंतालीस',
            'अड़तालीस',
            'उनचास',
            'पचास',
            'इक्यावन',
            'बावन',
            'तिरेपन',
            'चौवन',
            'पचपन',
            'छप्पन',
            'सत्तावन',
            'अट्ठावन',
            'उनसठ',
            'साठ',
            'इकसठ',
            'बासठ',
            'तिरसठ',
            'चौंसठ',
            'पैंसठ',
            'छियासठ',
            'सड़सठ',
            'अड़सठ',
            'उनहत्तर',
            'सत्तर',
            'इकहत्तर',
            'बहत्तर',
            'तिहत्तर',
            'चौहत्तर',
            'पचहत्तर',
            'छिहत्तर',
            'सतहत्तर',
            'अठहत्तर',
            'उनासी',
            'अस्सी',
            'इक्यासी',
            'बयासी',
            'तिरासी',
            'चौरासी',
            'पचासी',
            'छियासी',
            'सत्तासी',
            'अट्ठासी',
            'नवासी',
            'नब्बे',
            'इक्यानवे',
            'बानवे',
            'तिरानवे',
            'चौरानवे',
            'पचानवे',
            'छियानवे',
            'सत्तानवे',
            'अट्ठानवे',
            'निन्यानवे'
        ];

        // 1 - 99
        if (num < 100) {
            return ones[num];
        }

        // 100 - 999
        if (num < 1000) {

            const hundred = Math.floor(num / 100);
            const remainder = num % 100;

            let result = ones[hundred] + ' सौ';

            if (remainder > 0) {
                result += ' ' + numberToHindi(remainder);
            }

            return result;
        }

        // 1,000 - 99,999
        if (num < 100000) {

            const thousand = Math.floor(num / 1000);
            const remainder = num % 1000;

            let result = numberToHindi(thousand) + ' हजार';

            if (remainder > 0) {
                result += ' ' + numberToHindi(remainder);
            }

            return result;
        }

        // 1,00,000 - 99,99,999
        if (num < 10000000) {

            const lakh = Math.floor(num / 100000);
            const remainder = num % 100000;

            let result = numberToHindi(lakh) + ' लाख';

            if (remainder > 0) {
                result += ' ' + numberToHindi(remainder);
            }

            return result;
        }

        // 1 Crore+
        const crore = Math.floor(num / 10000000);
        const remainder = num % 10000000;

        let result = numberToHindi(crore) + ' करोड़';

        if (remainder > 0) {
            result += ' ' + numberToHindi(remainder);
        }

        return result;
    }


    // =========================================================
    // AMOUNT IN HINDI WORDS
    // =========================================================

    const hindiAmount = numberToHindi(amount);


    // =========================================================
    // CREATE HINDI VOICE MESSAGE
    // =========================================================

    let voiceMessage = '';

    if (receiverType === 'agent') {

        voiceMessage =
            receiverName +
            ' को ' +
            hindiAmount +
            ' रुपये का भुगतान किया गया है।';

    } else {

        voiceMessage =
            'एडमिन को ' +
            hindiAmount +
            ' रुपये का भुगतान किया गया है।';
    }


    // =========================================================
    // ESCAPE HTML
    // =========================================================

    function escapeHtml(value) {

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    // =========================================================
    // TEXT TO SPEECH
    // =========================================================
    //
    // FIX (v1.0.1 - undone): Pehle socha tha ki mixed-script
    // (English naam + Hindi sentence) ek saath bolne se ruk
    // jaata hai, isliye do utterances me toda tha.
    //
    // FIX (v1.0.2): Asli bug Chrome ka GARBAGE COLLECTION wala
    // tha — utterance object ka reference na hone se beech me
    // hi drop ho jaata tha.
    //
    // FIX (v1.0.3): Ab GC-safe reference hai, to WAPAS EK HI
    // utterance use kar rahe hain (poora voiceMessage ek saath),
    // hi-IN voice ke saath. Hindi voice engines Latin/English
    // naam ko bhi phonetically Hindi accent me bol dete hain —
    // isse na koi gap rehta hai, na awkward voice-switch, aur
    // sirf EK audio chalta hai.
    // =========================================================

    // Persistent reference — GC se bachne ke liye zaroori hai
    let activeUtterance = null;

    function speakPayment() {

        if (!('speechSynthesis' in window)) {

            console.log(
                'Speech Synthesis इस browser में supported नहीं है।'
            );

            return;
        }

        const voices = window.speechSynthesis.getVoices();

        // Voices abhi load nahi hui to thodi der baad retry karo
        if (voices.length === 0) {
            setTimeout(speakPayment, 300);
            return;
        }

        // Previous voice stop करें
        window.speechSynthesis.cancel();

        // Hindi voice खोजें
        let hindiVoice = voices.find(function (voice) {

            return voice.lang &&
                voice.lang
                    .toLowerCase()
                    .startsWith('hi');

        });


        // Hindi voice नहीं मिले तो Indian English
        if (!hindiVoice) {

            hindiVoice = voices.find(function (voice) {

                return voice.lang &&
                    voice.lang
                        .toLowerCase()
                        .startsWith('en-in');

            });
        }


        // =====================================================
        // EK HI UTTERANCE — poora voiceMessage (naam + sentence)
        // =====================================================

        const utterance = new SpeechSynthesisUtterance(voiceMessage);

        utterance.lang = 'hi-IN';
        utterance.rate = 0.85;
        utterance.pitch = 1;
        utterance.volume = 1;

        if (hindiVoice) {
            utterance.voice = hindiVoice;
        }

        utterance.onerror = function (e) {
            console.log('Payment voice speak error:', e.error);
        };

        // Global reference me store karo — GC isse hata na paaye
        activeUtterance = utterance;

        window.speechSynthesis.speak(utterance);
    }


    // =========================================================
    // LOAD AVAILABLE VOICES
    // =========================================================

    if ('speechSynthesis' in window) {

        window.speechSynthesis.getVoices();

        window.speechSynthesis.onvoiceschanged = function () {

            window.speechSynthesis.getVoices();

        };
    }


    // =========================================================
    // PAYMENT SUCCESS SWEETALERT
    // =========================================================

    Swal.fire({

        icon: 'success',

        title: 'भुगतान सफल रहा!',

        html: `
            <div style="
                font-size: 16px;
                color: #374151;
                margin-top: 8px;
            ">

                <!-- Amount -->
                <div style="
                    margin-bottom: 12px;
                ">

                    <strong style="
                        color: #14532D;
                        font-size: 26px;
                        font-family: 'Space Grotesk', sans-serif;
                    ">
                        ₹ ${formattedAmount}
                    </strong>

                </div>


                <!-- Receiver -->
                <div style="
                    font-size: 14px;
                    color: #6B7A7B;
                    margin-bottom: 6px;
                ">

                    <strong>
                        ${receiverType === 'agent'
                            ? 'एजेंट'
                            : 'रिसीवर'}:
                    </strong>

                    ${escapeHtml(receiverName)}

                </div>


                <!-- Order -->
                ${
                    orderNumber
                        ? `
                            <div style="
                                font-size: 13px;
                                color: #6B7A7B;
                                margin-top: 6px;
                            ">
                                ऑर्डर: #${escapeHtml(orderNumber)}
                            </div>
                          `
                        : ''
                }


                <!-- Hindi Amount -->
                <div style="
                    margin-top: 10px;
                    font-size: 13px;
                    color: #6B7A7B;
                ">
                    ${escapeHtml(hindiAmount)} रुपये
                </div>


                <!-- Voice Button -->
                <button
                    type="button"
                    id="playPaymentVoice"
                    style="
                        margin-top: 18px;
                        padding: 10px 22px;
                        border: none;
                        border-radius: 8px;
                        background: #14532D;
                        color: white;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                    "
                >
                    🔊 आवाज़ सुनें
                </button>

            </div>
        `,

        confirmButtonText: 'ठीक है',

        confirmButtonColor: '#16A34A',

        allowOutsideClick: false,

        allowEscapeKey: false,


        // =====================================================
        // SWEETALERT OPEN
        // =====================================================

        didOpen: function () {

            const playButton =
                document.getElementById('playPaymentVoice');


            // Manual voice button
            if (playButton) {

                playButton.addEventListener(
                    'click',
                    function () {

                        speakPayment();

                    }
                );
            }


            // Automatic voice
            setTimeout(function () {

                speakPayment();

            }, 700);

        }

    });

});
</script>

<?php endif; ?>


<!-- =========================================================
     COMMON HTML STRUCTURE CLOSE
     ये paymentSuccess पर depend नहीं करेगा
========================================================= -->

</main>
</div>


<!-- =========================================================
     SIDEBAR / MOBILE MENU
========================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const menuToggle =
        document.getElementById('menuToggle');

    const sidebar =
        document.getElementById('sidebar');

    const overlay =
        document.getElementById('sidebarOverlay');


    if (menuToggle && sidebar && overlay) {

        function toggleSidebar() {

            sidebar.classList.toggle('open');

            overlay.classList.toggle('active');

            document.body.style.overflow =
                sidebar.classList.contains('open')
                    ? 'hidden'
                    : '';
        }


        menuToggle.addEventListener(
            'click',
            toggleSidebar
        );


        overlay.addEventListener(
            'click',
            toggleSidebar
        );


        window.addEventListener(
            'resize',
            function () {

                if (
                    window.innerWidth > 768 &&
                    sidebar.classList.contains('open')
                ) {

                    sidebar.classList.remove('open');

                    overlay.classList.remove('active');

                    document.body.style.overflow = '';
                }

            }
        );
    }


    // =========================================================
    // AUTO HIDE NORMAL ALERTS
    // =========================================================

    document
        .querySelectorAll('.alert')
        .forEach(function (alert) {

            setTimeout(function () {

                alert.style.transition =
                    'opacity 0.5s ease';

                alert.style.opacity = '0';


                setTimeout(function () {

                    if (alert.parentElement) {
                        alert.remove();
                    }

                }, 500);

            }, 5000);

        });

});
</script>


</body>
</html>