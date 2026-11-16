@extends('legal.layout')
@section('title', 'Privacy Policy')
@section('document_name', 'Privacy Policy')

@section('legal_content')
<p class="policy-lead">CHICK'S POPS respects your privacy. This notice explains how customer information is handled through CrispyCloud when you browse, register, place an order, or contact the store. It is prepared with reference to the Philippine Data Privacy Act of 2012 (Republic Act No. 10173) and applicable rules.</p>

<section><h2>1. Personal information controller</h2>
<p>The business operating CHICK'S POPS decides why and how customer information is used for the ordering service and acts as the personal information controller. Its verified identity and contact details are listed in Section 11.</p>
</section>

<section><h2>2. Information we process</h2>
<ul>
<li><strong>Account details:</strong> full name, email address, contact number and a securely hashed password used to authenticate your account.</li>
<li><strong>Order and delivery details:</strong> ordered products, quantities, customizations, notes, delivery recipient, address, phone number, order and delivery statuses, and prior orders.</li>
<li><strong>Payment information:</strong> chosen payment method, amounts, payment status and any GCash reference you choose to submit for manual verification. The website is not a GCash payment gateway and does not require a GCash PIN or OTP.</li>
<li><strong>Technical information:</strong> necessary session cookies and technical records that support sign-in, cart persistence, security and troubleshooting, as configured in the deployed application.</li>
</ul>
</section>

<section><h2>3. Why we process your information</h2>
<p>We use necessary information to create and secure accounts, identify returning customers, keep a session-based shopping cart, process and fulfil orders, contact the recipient for delivery, verify payments, show order status and history, respond to concerns, prevent misuse, maintain accurate business records, and comply with applicable legal duties.</p>
<p>Depending on the specific activity, processing may rely on fulfilling an agreement with you, compliance with law, legitimate interests that do not override your rights, or consent when required. We do not treat accepting the Terms as blanket consent for unrelated marketing.</p>
</section>

<section><h2>4. How accounts and passwords work</h2>
<p>Your email address identifies the account during sign-in. Your password is checked against its stored password hash. Authorized administrators manage orders and related customer delivery details through a separate, protected interface; they do not need your password to perform their duties. Guests may view the menu without registering, but must sign in to complete an order.</p>
</section>

<section><h2>5. Sharing and access</h2>
<p>Access is limited according to operational need. Customer order information may be available to authorized store administrators and, where delivery requires it, authorized delivery personnel. Where services are used for hosting, technical maintenance, payment processing, or legally required reporting, relevant information may be handled by those providers or authorities as permitted by law. We do not publish customers' passwords, personal addresses or payment references on the public menu.</p>
</section>

<section><h2>6. Cookies and automated processing</h2>
<p>The website uses necessary session technology to maintain sign-in state, protect forms, and preserve the shopping cart. Browsing and order status functions are based on customer choices and saved database records; they do not provide GPS tracking of a delivery rider. Any additional analytics or advertising technology introduced in future must be assessed and disclosed before use.</p>
</section>

<section><h2>7. Data retention</h2>
<p>We retain information only as long as needed for the purposes described and for applicable legal, accounting, security and dispute-resolution obligations. The store must publish its actual retention schedule for these categories before launching to real customers:</p>
<dl class="policy-contacts">
<div><dt>Customer account information</dt><dd>{{ config('commerce_legal.account_retention') ?: 'Retention period must be specified by the store before public launch.' }}</dd></div>
<div><dt>Order, payment and transaction records</dt><dd>{{ config('commerce_legal.transaction_retention') ?: 'Retention period must be specified by the store before public launch.' }}</dd></div>
<div><dt>Technical and security records</dt><dd>{{ config('commerce_legal.technical_retention') ?: 'Retention period must be specified by the store before public launch.' }}</dd></div>
</dl>
<p>When no longer needed and no law requires continued retention, information should be securely deleted or de-identified using the operator's approved procedures.</p>
</section>

<section><h2>8. Safeguarding information</h2>
<p>Appropriate access controls, password hashing, application security practices and restricted administrator permissions should protect customer records. No internet system can guarantee absolute security. Customers should use strong passwords, avoid sharing credentials, and promptly report suspected account misuse. The operator is responsible for applying security and breach-notification procedures required by law.</p>
</section>

<section><h2>9. Your rights</h2>
<p>Subject to the Philippine Data Privacy Act and applicable exceptions, you may ask to be informed about processing, access your data, request correction, object in appropriate cases, seek erasure or blocking where legally available, request portability where applicable, and raise a complaint with the National Privacy Commission. You may also withdraw consent for processing that genuinely relies on your consent, subject to applicable limitations.</p>
<p>To exercise these rights, contact the business using the details in Section 11. Identity verification may be necessary to protect your information. Some order and accounting records may need to be kept where required by law.</p>
</section>

<section><h2>10. Changes to this policy</h2>
<p>We may update this notice when the website, its processing practices or applicable law changes. The updated document will carry a new effective date; material changes should be brought to users' attention when appropriate.</p>
</section>

<section><h2>11. Privacy enquiries</h2>
@include('legal.partials.contact')
</section>
@endsection
