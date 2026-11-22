@extends('legal.layout')
@section('title', 'Terms & Conditions')
@section('document_name', 'Terms & Conditions')

@section('legal_content')
<p class="policy-lead">These Terms &amp; Conditions govern customer accounts, online orders and use of the CHICK'S POPS ordering website (CrispyCloud). Please read them before creating an account or placing an order.</p>

<section><h2>1. Who operates this service</h2>
<p>CHICK'S POPS is the store presented on this website. The service is operated by <strong>{{ config('commerce_legal.operator_name') ?: 'the business operator identified in the contact section below' }}</strong>. The website provides food and beverage browsing, customization, ordering, order-status tracking, and account management. A separate administrator interface handles order fulfilment, inventory and sales records.</p>
</section>

<section><h2>2. Customer accounts and credentials</h2>
<p>To place a customer order, you must create or sign in to an account. Provide accurate, up-to-date information, including your full name, email address and contact phone number. You are responsible for keeping your password confidential and for activity performed using your account. Notify the store promptly if you suspect unauthorized access.</p>
<p>Passwords are used for authentication; store administrators should not ask you to share your password, one-time PIN, or GCash MPIN. Guests may browse the menu without an account. An account is needed to complete checkout.</p>
</section>

<section><h2>3. Products, availability and prices</h2>
<p>Product descriptions, optional extras and displayed prices are intended to accurately describe available offerings. Availability may change. Before you confirm an order, the checkout displays the product subtotal, applicable delivery fee and total. A customization or optional drink may change the total. If an item or ingredient is unavailable, the store may contact you about available options or cancellation.</p>
</section>

<section><h2>4. Ordering and fulfilment</h2>
<p>Review your cart, quantities, add-ons, delivery address, contact details and payment selection before submitting an order. Orders are recorded in the system and tracked by status. The tracking page reports recorded order statuses only; it is not live GPS tracking. Delivery times may vary because of preparation, location, weather, stock or courier conditions. Please provide a reachable delivery contact.</p>
</section>

<section><h2>5. Payment</h2>
<p>Available payment options may include cash on delivery (COD) or GCash with manual verification by store administrators. Selecting GCash or entering a transaction reference does not itself confirm that payment has been received. Only an authorized administrator can verify the payment status. Never submit your GCash PIN, OTP or account password through the website or to a person claiming to be support.</p>
</section>

<section><h2>6. Cancellation, problems and refunds</h2>
<p>Where the website offers the feature, customers may cancel an <strong>unpaid order marked Preparing</strong> while online cancellation remains available. Once an order has been delivered or paid, online self-cancellation is unavailable; contact the store promptly for assistance. Incorrect orders, missing items, damaged products, or other concerns will be assessed under applicable Philippine consumer laws. Nothing in these Terms limits rights or remedies guaranteed by law. Where a refund is due, the store will communicate the available refund method and handling process.</p>
</section>

<section><h2>7. Proper use of the website</h2>
<p>You agree not to provide false order information, impersonate another customer, attempt unauthorized access to customer or administrator accounts, interfere with the website, or misuse payment references. The store may restrict misuse or investigate suspicious activity, consistent with applicable law and the rights of customers.</p>
</section>

<section><h2>8. Personal information</h2>
<p>Personal information is used for account access, order fulfilment, payment verification and customer support as described in our <a href="{{ route('legal.privacy') }}">Privacy Policy</a>. The Privacy Policy explains the information collected, its purposes, who may receive it, retention, and how customers can exercise their rights.</p>
</section>

<section><h2>9. Service interruptions and updates</h2>
<p>We may temporarily suspend access for maintenance or security, and may update menus or operational processes. Changes to these Terms will be published with a revised effective date. Material changes should be communicated appropriately. These Terms do not remove the store's duties under Philippine law.</p>
</section>

<section><h2>10. Applicable law and customer concerns</h2>
<p>These Terms are governed by applicable laws of the Republic of the Philippines, including relevant consumer and e-commerce protections. Customers should first contact the store about order or account concerns. This does not restrict access to government regulators or remedies available under law.</p>
</section>

<section><h2>11. Contact the store</h2>
@include('legal.partials.contact')
</section>
@endsection
