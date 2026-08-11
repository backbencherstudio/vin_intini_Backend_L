<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription Flow Test</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, sans-serif;
        }

        body {
            background: #f4f7f9;
            margin: 0;
            padding: 24px;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 16px;
            color: #043940;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            padding: 20px;
            margin-bottom: 16px;
        }

        .card h2 {
            font-size: 16px;
            margin: 0 0 12px;
            color: #043940;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #333;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd6dd;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        button {
            padding: 10px 18px;
            border: 0;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            color: #fff;
            background: #00a8ae;
            transition: opacity .2s;
        }

        button:hover {
            opacity: .85;
        }

        button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        button.secondary {
            background: #5b6b79;
        }

        .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .plan {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            cursor: pointer;
            flex: 1;
            min-width: 160px;
        }

        .plan.selected {
            border-color: #00a8ae;
            background: #f0fdfa;
        }

        .plan .price {
            font-size: 20px;
            font-weight: 700;
            color: #043940;
        }

        #card-element {
            border: 1px solid #ccd6dd;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
        }

        #card-errors,
        #messages {
            font-size: 13px;
            white-space: pre-wrap;
            font-family: ui-monospace, monospace;
        }

        #card-errors {
            color: #dc2626;
            margin-bottom: 12px;
        }

        #messages {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 12px;
            min-height: 60px;
            max-height: 300px;
            overflow: auto;
            color: #0f172a;
        }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .badge.ok {
            background: #dcfce7;
            color: #166534;
        }

        .badge.waiting {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge.fail {
            background: #fee2e2;
            color: #991b1b;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Subscription Flow Test</h1>

        <div class="card">
            <h2>1. API Token (JWT)</h2>
            <input type="password" id="token" placeholder="Paste your JWT token here">
            <button onclick="loadPlans()" id="load-plans-btn">Load Plans</button>
        </div>

        <div class="card hidden" id="plans-card">
            <h2>2. Select a Plan</h2>
            <div class="row" id="plans"></div>
        </div>

        <div class="card hidden" id="otp-card">
            <h2>3. Verify Email (OTP)</h2>
            <button onclick="sendOtp()" id="send-otp-btn">Send OTP</button>
            <span id="otp-status"></span>
            <input type="text" id="otp" placeholder="Enter the 4-digit OTP from your email" maxlength="4" class="hidden">
        </div>

        <div class="card hidden" id="payment-card">
            <h2>4. Card Details</h2>
            <div id="card-element"></div>
            <div id="card-errors"></div>
            <button onclick="subscribe()" id="subscribe-btn">Subscribe</button>
        </div>

        <div class="card">
            <h2>Log</h2>
            <div id="messages">Waiting...</div>
        </div>
    </div>

    <script>
        const state = {
            stripe: null,
            cardElement: null,
            plans: [],
            selectedPlanId: null,
            token: '',
        };

        function log(message, type = 'info') {
            const el = document.getElementById('messages');
            const prefix = type === 'ok' ? '✓' : type === 'fail' ? '✗' : '→';
            el.textContent = `[${new Date().toLocaleTimeString()}] ${prefix} ${message}\n` + el.textContent;
        }

        function authHeaders() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + state.token,
            };
        }

        async function api(path, options = {}) {
            const res = await fetch('/api' + path, {
                ...options,
                headers: authHeaders(),
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(body.message || body.error || 'Request failed (' + res.status + ')');
            }
            return body;
        }

        async function loadPlans() {
            state.token = document.getElementById('token').value.trim();
            if (!state.token) {
                log('Paste a valid JWT token first.', 'fail');
                return;
            }

            try {
                const { data } = await api('/plans');
                state.stripe = Stripe(data.stripe_public_key);
                state.plans = data.plans;

                log(`Stripe key loaded: ${data.stripe_public_key.slice(0, 12)}...`);
                log(`Plans: ${state.plans.map(p => p.name).join(', ')}`, 'ok');

                const plansEl = document.getElementById('plans');
                plansEl.innerHTML = '';
                state.plans.forEach((plan, index) => {
                    const div = document.createElement('div');
                    div.className = 'plan' + (index === 0 ? ' selected' : '');
                    div.innerHTML = `
                        <strong>${plan.name}</strong><br>
                        <span class="price">$${plan.billing_rate}</span> / ${plan.billing_cycle}<br>
                        <small>${plan.stripe_price_id}</small>
                    `;
                    div.onclick = () => selectPlan(plan.id, div);
                    plansEl.appendChild(div);
                });

                state.selectedPlanId = state.plans[0]?.id;
                document.getElementById('plans-card').classList.remove('hidden');
                document.getElementById('otp-card').classList.remove('hidden');
            } catch (error) {
                log(error.message, 'fail');
            }
        }

        function selectPlan(planId, el) {
            state.selectedPlanId = planId;
            document.querySelectorAll('.plan').forEach(p => p.classList.remove('selected'));
            el.classList.add('selected');
            log('Selected plan #' + planId);
        }

        async function sendOtp() {
            const btn = document.getElementById('send-otp-btn');
            btn.disabled = true;

            try {
                const { data } = await api('/subscriptions/send-otp', {
                    method: 'POST',
                    body: JSON.stringify({ plan_id: state.selectedPlanId }),
                });
                log('OTP sent to ' + data.email + '. Check your inbox.', 'ok');
                document.getElementById('otp-status').innerHTML =
                    '<span class="badge waiting">OTP sent — check email</span>';
                document.getElementById('otp').classList.remove('hidden');
                document.getElementById('payment-card').classList.remove('hidden');
                mountCardElement();
            } catch (error) {
                log('send-otp: ' + error.message, 'fail');
                document.getElementById('otp-status').innerHTML =
                    '<span class="badge fail">' + error.message + '</span>';
            } finally {
                btn.disabled = false;
            }
        }

        function mountCardElement() {
            if (state.cardElement) return;

            const elements = state.stripe.elements();
            state.cardElement = elements.create('card', { hidePostalCode: true });
            state.cardElement.mount('#card-element');
            state.cardElement.on('change', (event) => {
                document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
            });
            log('Card element mounted (test card: 4242 4242 4242 4242, any future date, any CVC).');
        }

        async function subscribe() {
            const btn = document.getElementById('subscribe-btn');
            btn.disabled = true;
            document.getElementById('card-errors').textContent = '';

            const otp = document.getElementById('otp').value.trim();
            if (!otp) {
                log('Enter the OTP first.', 'fail');
                btn.disabled = false;
                return;
            }

            try {
                log('Creating PaymentMethod...');
                const { paymentMethod, error } = await state.stripe.createPaymentMethod({
                    type: 'card',
                    card: state.cardElement,
                });

                if (error) {
                    throw new Error(error.message);
                }

                log('PaymentMethod created: ' + paymentMethod.id, 'ok');

                const { data } = await api('/subscriptions/create', {
                    method: 'POST',
                    body: JSON.stringify({
                        plan_id: state.selectedPlanId,
                        otp: otp,
                        payment_method: paymentMethod.id,
                    }),
                });

                if (data.payment_intent_client_secret) {
                    log('3DS required — confirming card payment...', 'waiting');
                    document.getElementById('messages').innerHTML =
                        '<span class="badge waiting">3DS / SCA — complete the bank dialog</span>' +
                        document.getElementById('messages').innerHTML;

                    const result = await state.stripe.confirmCardPayment(data.payment_intent_client_secret);

                    if (result.error) {
                        log('3DS failed: ' + result.error.message, 'fail');
                    } else {
                        log('Payment confirmed! Status: ' + result.paymentIntent.status, 'ok');
                        log('Subscription will be activated via webhook. Check GET /subscriptions/status.');
                    }
                } else {
                    log('Subscription active!', 'ok');
                    log(JSON.stringify(data.subscription, null, 2), 'ok');
                }
            } catch (error) {
                log('subscribe: ' + error.message, 'fail');
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>
