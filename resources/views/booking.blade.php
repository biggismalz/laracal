<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Book a Service</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.0/dist/cdn.min.js"></script>
    <style>
        :root {
            color-scheme: light;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        body {
            margin: 0;
            background: #f5f7fb;
            color: #1f2937;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 3rem 1.5rem 4rem;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
            padding: 2.5rem;
            margin-bottom: 2rem;
        }
        .card h2 {
            margin-top: 0;
        }
        .service-grid {
            display: grid;
            gap: 1.25rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .service-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem;
            transition: border 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            cursor: pointer;
            background: #fff;
        }
        .service-card.active {
            border-color: #6366f1;
            box-shadow: 0 16px 32px rgba(99, 102, 241, 0.18);
            transform: translateY(-2px);
        }
        .slots-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }
        .slot-button {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
            transition: border 0.2s ease, background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            font-weight: 600;
        }
        .slot-button:hover {
            border-color: #6366f1;
        }
        .slot-button.active {
            background: #6366f1;
            border-color: #6366f1;
            color: #fff;
            box-shadow: 0 14px 28px rgba(99, 102, 241, 0.25);
            transform: translateY(-1px);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.35rem;
            font-weight: 600;
            color: #111827;
        }
        .form-control, .form-textarea {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            padding: 0.75rem 0.85rem;
            font-size: 1rem;
            transition: border 0.2s ease, box-shadow 0.2s ease;
            font-family: inherit;
        }
        .form-control:focus, .form-textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            font-size: 0.875rem;
            background: #eef2ff;
            color: #3730a3;
            font-weight: 600;
        }
        .btn-primary {
            background: #111827;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 0.95rem 1.35rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover {
            background: #000;
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(17, 24, 39, 0.18);
        }
        .btn-primary[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .status-banner {
            border-radius: 12px;
            padding: 1rem 1.2rem;
            font-weight: 600;
        }
        .status-success {
            background: #ecfdf5;
            color: #047857;
        }
        .status-error {
            background: #fef2f2;
            color: #b91c1c;
        }
        .muted {
            color: #6b7280;
        }
        .inline-options {
            display: flex;
            gap: 1rem;
        }
        .inline-options label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        [x-cloak] { display: none !important; }
        @media (max-width: 640px) {
            .card {
                padding: 1.75rem 1.5rem;
            }
            .service-grid, .slots-grid {
                grid-template-columns: 1fr;
            }
            .inline-options {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    @php
        $bookingConfig = [
            'services' => $services,
            'defaultDate' => $defaultDate,
            'slotsEndpoint' => route('booking.slots', ['service' => '__ID__']),
            'bookingEndpoint' => route('booking.store'),
        ];
    @endphp

    <script>
        window.bookingConfig = @js($bookingConfig);
    </script>

    <div class="container" x-data="bookingFlow()" x-init="init()">
        <header style="margin-bottom: 2rem;">
            <p class="tag">Laracal</p>
            <h1 style="font-size: clamp(2rem, 4vw, 2.8rem); margin: 0.75rem 0 0.5rem;">Schedule a service</h1>
            <p class="muted" style="max-width: 600px;">Select the detailing package that suits you, choose an available slot, and confirm your details. We’ll reserve the appointment and guide you through&nbsp;payment.</p>
        </header>

        <div class="card" x-show="services.length" x-cloak>
            <h2>Select a service</h2>
            <p class="muted" style="margin-bottom: 1.5rem;">Each service includes buffers for preparation and handover.</p>
            <div class="service-grid">
                <template x-for="service in services" :key="service.id">
                    <article class="service-card" :class="selectedService && selectedService.id === service.id ? 'active' : ''" @click="selectService(service)">
                        <header>
                            <h3 style="margin: 0 0 0.35rem; font-size: 1.15rem;" x-text="service.name"></h3>
                            <p class="muted" style="margin: 0 0 1rem;" x-text="service.description || 'No description provided.'"></p>
                        </header>
                        <dl style="margin: 0; display: grid; gap: 0.5rem; font-size: 0.95rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <dt class="muted">Duration</dt>
                                <dd><span x-text="service.duration_minutes"></span> mins</dd>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <dt class="muted">Price</dt>
                                <dd x-text="formatPrice(service.price_cents, service.currency)"></dd>
                            </div>
                            <template x-if="service.deposit_cents">
                                <div style="display: flex; justify-content: space-between;">
                                    <dt class="muted">Deposit</dt>
                                    <dd x-text="formatPrice(service.deposit_cents, service.currency)"></dd>
                                </div>
                            </template>
                        </dl>
                    </article>
                </template>
            </div>
        </div>

        <template x-if="!services.length">
            <div class="card">
                <h2>No services available</h2>
                <p class="muted">Please check back soon—our booking calendar will be live shortly.</p>
            </div>
        </template>

        <template x-if="selectedService">
            <div class="card">
                <h2>Pick a date</h2>
                <div class="form-group" style="max-width: 260px;">
                    <label for="booking-date">Available dates</label>
                    <input id="booking-date" type="date" class="form-control" x-model="selectedDate" :min="defaultDate" @change="loadSlots()" />
                </div>

                <div x-show="loadingSlots" class="status-banner status-success" style="background: #eef2ff; color: #4338ca; border: 1px solid rgba(99, 102, 241, 0.2);">
                    Checking availability for <span x-text="displayDate"></span>…
                </div>

                <template x-if="!loadingSlots && slots.length === 0">
                    <div class="status-banner status-error" style="background: #fef3c7; color: #92400e; border: 1px solid rgba(217, 119, 6, 0.2);">
                        No open slots on <span x-text="displayDate"></span>. Try another date or service.
                    </div>
                </template>

                <div class="slots-grid" x-show="slots.length">
                    <template x-for="slot in slots" :key="slot.start">
                        <button type="button" class="slot-button" :class="selectedSlot && selectedSlot.start === slot.start ? 'active' : ''" @click="selectSlot(slot)" x-text="slot.label"></button>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="selectedService && selectedSlot">
            <div class="card">
                <h2>Tell us about you</h2>
                <p class="muted" style="margin-bottom: 1.5rem;">
                    You selected <strong x-text="selectedService.name"></strong> on
                    <strong x-text="displayDate"></strong> at
                    <strong x-text="selectedSlot.label"></strong>.
                </p>

                <template x-if="errorMessage">
                    <div class="status-banner status-error" x-text="errorMessage"></div>
                </template>

                <template x-if="successMessage">
                    <div class="status-banner status-success" x-text="successMessage"></div>
                </template>

                <form @submit.prevent="submitBooking" x-show="!successMessage">
                    <div class="form-group">
                        <label for="customer-name">Name</label>
                        <input id="customer-name" type="text" class="form-control" x-model="form.customer_name" required />
                    </div>

                    <div class="form-group">
                        <label for="customer-email">Email</label>
                        <input id="customer-email" type="email" class="form-control" x-model="form.customer_email" required />
                    </div>

                    <div class="form-group">
                        <label for="customer-phone">Phone (optional)</label>
                        <input id="customer-phone" type="tel" class="form-control" x-model="form.customer_phone" placeholder="e.g. 07123 456789" />
                    </div>

                <div class="form-group">
                        <label for="customer-notes">Notes (optional)</label>
                        <textarea id="customer-notes" class="form-textarea" x-model="form.customer_notes" placeholder="Share vehicle details, access notes, or anything else we should know."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Payment preference</label>
                        <div class="inline-options">
                            <label>
                                <input type="radio" value="full" x-model="form.payment_option">
                                Pay full (<span x-text="formatPrice(selectedService.price_cents, selectedService.currency)"></span>)
                            </label>
                            <template x-if="selectedService.deposit_cents">
                                <label>
                                    <input type="radio" value="deposit" x-model="form.payment_option">
                                    Pay deposit (<span x-text="formatPrice(selectedService.deposit_cents, selectedService.currency)"></span>)
                                </label>
                            </template>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" :disabled="submitting">
                        <span x-show="!submitting">Reserve &amp; continue to payment</span>
                        <span x-show="submitting">Saving…</span>
                    </button>
                </form>
            </div>
        </template>
    </div>

    <script>
        window.bookingFlow = () => {
            const config = window.bookingConfig ?? {};

            return {
                services: config.services ?? [],
                defaultDate: config.defaultDate,
                slotsEndpoint: config.slotsEndpoint,
                bookingEndpoint: config.bookingEndpoint,
                selectedService: null,
                selectedDate: config.defaultDate,
                slots: [],
                selectedSlot: null,
                loadingSlots: false,
                submitting: false,
                successMessage: null,
                errorMessage: null,
                form: {
                    customer_name: '',
                    customer_email: '',
                    customer_phone: '',
                    customer_notes: '',
                    payment_option: 'full',
                },
                init() {
                    if (this.services.length > 0) {
                        this.selectService(this.services[0]);
                    }
                },
                get displayDate() {
                    const date = new Date(this.selectedDate + 'T00:00:00');
                    return date.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
                },
                formatPrice(cents, currency) {
                    if (cents === null || cents === undefined) {
                        return '-';
                    }

                    return new Intl.NumberFormat(undefined, {
                        style: 'currency',
                        currency: currency || 'GBP',
                    }).format(cents / 100);
                },
                selectService(service) {
                    this.selectedService = service;
                    this.form.payment_option = service.deposit_cents ? 'deposit' : 'full';
                    this.selectedSlot = null;
                    this.successMessage = null;
                    this.errorMessage = null;
                    this.loadSlots();
                },
                selectSlot(slot) {
                    this.selectedSlot = slot;
                    this.successMessage = null;
                    this.errorMessage = null;
                },
                async loadSlots() {
                    if (!this.selectedService) {
                        return;
                    }

                    this.loadingSlots = true;
                    this.slots = [];
                    this.selectedSlot = null;
                    this.errorMessage = null;

                    try {
                        const url = this.slotsEndpoint.replace('__ID__', this.selectedService.id) + `?date=${this.selectedDate}`;
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Unable to load availability');
                        }

                        const payload = await response.json();
                        this.slots = payload.data ?? [];
                    } catch (error) {
                        console.error(error);
                        this.errorMessage = 'We could not load availability right now. Please reload the page or try again later.';
                    } finally {
                        this.loadingSlots = false;
                    }
                },
                async submitBooking() {
                    if (!this.selectedService || !this.selectedSlot) {
                        this.errorMessage = 'Please choose a service and time slot before continuing.';
                        return;
                    }

                    this.submitting = true;
                    this.errorMessage = null;
                    this.successMessage = null;

                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    try {
                        const response = await fetch(this.bookingEndpoint, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                service_id: this.selectedService.id,
                                scheduled_start: this.selectedSlot.start,
                                customer_name: this.form.customer_name,
                                customer_email: this.form.customer_email,
                                customer_phone: this.form.customer_phone,
                                customer_notes: this.form.customer_notes,
                                payment_option: this.form.payment_option,
                            }),
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'We could not save your booking.');
                        }

                        if (payload.checkout_url) {
                            window.location.href = payload.checkout_url;
                            return;
                        }

                        this.successMessage = payload.message || 'Booking saved. We will redirect you to payment shortly.';
                    } catch (error) {
                        console.error(error);
                        this.errorMessage = error.message || 'Something went wrong. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        };
    </script>
</body>
</html>
