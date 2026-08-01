<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-2xl px-6 py-6 text-white">
            <h1 class="text-xl font-bold">VinIntini Premium</h1>
            <p class="text-indigo-100 text-sm mt-1">Paste the client secret, generate the form and pay</p>
        </div>

        <div class="bg-white rounded-b-2xl shadow-xl px-6 py-6">
            <!-- Step 1: client secret -->
            <div id="inputs-section">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">1. Paste the client secret</h2>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">client_secret</label>
                    <input id="input-client-secret" type="text" value="pi_3TzV0mBCmBVS6SSQ0Y6z6PEw_secret_YvI5XZ82zDAL9fytpGTNnHbUY"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Publishable key comes from the frontend .env automatically</p>
                </div>

                <button id="generate-btn" type="button"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition mt-5">
                    Generate payment form
                </button>
            </div>

            <!-- Step 2: Stripe payment element -->
            <div id="payment-section" class="hidden">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    2. Complete payment
                    <span class="text-gray-400 font-normal">(test card: <code class="bg-gray-100 px-1 rounded">4242 4242 4242 4242</code>)</span>
                </h2>

                <form id="payment-form">
                    <div id="payment-element" class="mb-4"></div>
                    <div id="payment-message" class="hidden text-red-600 text-sm mb-4"></div>

                    <button id="submit" type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="button-text">Pay now</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // Publishable key from the frontend .env (config/services.php -> stripe.key)
        const publishableKey = '{{ config('services.stripe.key') }}';
        const returnUrl = '{{ url('/test-payment/success') }}';

        let stripe = null;
        let elements = null;
        let paymentElement = null;
        let clientSecret = null;

        document.getElementById('generate-btn').addEventListener('click', () => {
            clientSecret = document.getElementById('input-client-secret').value.trim();

            if (!clientSecret) {
                alert('client_secret is required');
                return;
            }

            stripe = Stripe(publishableKey);
            elements = stripe.elements({ clientSecret: clientSecret });
            paymentElement = elements.create('payment', { layout: 'tabs' });
            paymentElement.mount('#payment-element');

            document.getElementById('inputs-section').classList.add('hidden');
            document.getElementById('payment-section').classList.remove('hidden');
        });

        document.getElementById('payment-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submit');
            const buttonText = document.getElementById('button-text');
            const message = document.getElementById('payment-message');

            submitBtn.disabled = true;
            buttonText.textContent = 'Processing...';

            const { error: submitError } = await elements.submit();
            if (submitError) {
                message.textContent = submitError.message;
                message.classList.remove('hidden');
                submitBtn.disabled = false;
                buttonText.textContent = 'Pay now';
                return;
            }

            const confirmParams = { return_url: returnUrl };

            // seti_ prefix = SetupIntent, anything else = PaymentIntent
            let result;
            if (clientSecret.startsWith('seti_')) {
                result = await stripe.confirmSetup({ elements, clientSecret, confirmParams });
            } else {
                result = await stripe.confirmPayment({ elements, clientSecret, confirmParams });
            }

            if (result.error) {
                message.textContent = result.error.message;
                message.classList.remove('hidden');
                submitBtn.disabled = false;
                buttonText.textContent = 'Pay now';
            }
        });
    </script>
</body>
</html>
