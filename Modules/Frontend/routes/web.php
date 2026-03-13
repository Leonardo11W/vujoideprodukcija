
<?php

use Illuminate\Support\Facades\Route;
use Modules\Frontend\Http\Controllers\Backend\AuthController;
use Modules\Frontend\Http\Controllers\Backend\FrontendsController;
use Modules\Frontend\Http\Controllers\Backend\UserController;
use Modules\Frontend\Http\Controllers\Backend\ServiceController;
use Modules\Frontend\Http\Controllers\Backend\ProductController;
use Modules\Frontend\Http\Controllers\Backend\PackageController;
use Modules\Frontend\Http\Controllers\Backend\CategoryController;
use Modules\Frontend\Http\Controllers\Backend\BranchController;
use Modules\Frontend\Http\Controllers\Backend\ExpertController;
use App\Http\Controllers\LanguageController;
use Modules\Frontend\Http\Controllers\Backend\CartController;
use Modules\Frontend\Http\Controllers\Backend\BankController;
use Modules\Frontend\Http\Controllers\Backend\BlogController;
use Modules\Frontend\Http\Controllers\Backend\WalletController;
use Modules\Frontend\Http\Controllers\Backend\ProfilePackageController;
use Modules\Frontend\Http\Controllers\Backend\BookingPaymentController;
use App\Http\Controllers\WishlistController;
use Modules\Frontend\Http\Controllers\PaymentController;
use Modules\Product\Http\Controllers\Backend\API\OrdersController;
use Modules\Frontend\Http\Controllers\ReviewController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
 *
 * Backend Routes
 *
 * --------------------------------------------------------------------
 */

Route::get('/address/get-countries', [UserController::class, 'getCountries'])->name('frontend.address.get-countries');
Route::get('/address/get-states', [UserController::class, 'getStates'])->name('frontend.address.get-states');
Route::get('/address/get-cities', [UserController::class, 'getCities'])->name('frontend.address.get-cities');

Route::middleware('vendor.mode')->group(function () {
    Route::get('language/{language}', [LanguageController::class, 'switch'])->name('frontend.language.switch');

    Route::get('/register', [AuthController::class, 'Signup'])->name('register-page');
    Route::post('/changepassword', [Modules\Frontend\Http\Controllers\Backend\AuthController::class, 'updatePassword'])->name('changepassword.update');

    Route::middleware('checkInstallation')->group(function () {
        Route::get('/', [FrontendsController::class, 'index'])->name('index');

        Route::get('/get-available-slots', [FrontendsController::class, 'getAvailableSlots'])->name('get-available-slots');
        Route::get('/get-coupon-list', [FrontendsController::class, 'couponlist'])->name('get-coupon-list');

        Route::get('/about', [FrontendsController::class, 'About'])->name('about');

        Route::get('/blog', [FrontendsController::class, 'Blog'])->name('blog');
        Route::get('/blog-details', [FrontendsController::class, 'BlogDetails'])->name('blog-details');

        Route::get('/blog-details/{id}', [BlogController::class, 'blogDetails'])->name('blog-details');

        Route::get('/blogs', [BlogController::class, 'blogsList'])->name('blogs');

        Route::get('/services/{category?}', [ServiceController::class, 'index'])->name('service');
        Route::get('/services-data', [ServiceController::class, 'getServiceCardsData'])->name('frontend.services.data');


        Route::get('/branch', [BranchController::class, 'index'])->name('branch');
        Route::get('/branch-detail', [BranchController::class, 'BranchDetail'])->name('branch-detail');
        Route::get('/branch-detail/{id}', [BranchController::class, 'BranchDetail'])->name('branch-detail');
        Route::post('/branch/select', [BranchController::class, 'selectBranch'])->name('branch.select');

        Route::get('/category', [CategoryController::class, 'index'])->name('category');
        Route::get('/category/data', [CategoryController::class, 'categoriesData'])->name('frontend.categories.data');

        Route::get('/sub-category', [CategoryController::class, 'SubCategory'])->name('sub-category');

        Route::get('/profile', [UserController::class, 'Profile'])->name('profile');
        Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');

        Route::get('/shop', [ProductController::class, 'index'])->name('shop');
        Route::get('/product-detail', [ProductController::class, 'ProductDetail'])->name('product-detail');

        Route::get('/product-detail/{slug}', [ProductController::class, 'ProductDetail'])->name('product-detail');
        Route::get('/shop/data', [ProductController::class, 'productsData'])->name('frontend.products.data');
        Route::get('/shop/{slug}', [ProductController::class, 'ProductDetail'])->name('product-detail');
        Route::get('products/filter', [ProductController::class, 'filterProducts'])->name('products.filter');


        Route::get('/contact', [FrontendsController::class, 'Contact'])->name('contact');

        Route::get('/signup', [AuthController::class, 'Signup'])->name('signup');
        Route::post('/register', [AuthController::class, 'SignupUser'])->name('register');
        Route::post('/check-contact-number', [AuthController::class, 'checkContactNumber'])->name('check.contact.number');
        Route::post('/check-email-availability', [AuthController::class, 'checkEmailAvailability'])->name('check.email.availability');
        Route::get('/login', [AuthController::class, 'Login'])->name('login');

        Route::get('/forgotpassword', [AuthController::class, 'ForgotPassword'])->name('forgotpassword');
        Route::get('/signup', [AuthController::class, 'Signup'])->name('signup');
        Route::post('/register', [AuthController::class, 'SignupUser'])->name('register');
        Route::get('/login', [AuthController::class, 'Login'])->name('login');
        
        // Google Authentication Routes
        Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
        Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
        
        Route::post('/user-logout', [Modules\Frontend\Http\Controllers\Backend\AuthController::class, 'logout'])->name('website.logout');

        Route::get('/forgotpassword', [AuthController::class, 'ForgotPassword'])->name('password.request');
        Route::post('/forgotpassword', [AuthController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'updateResetPassword'])->name('password.update');

        Route::get('/newpassword', [AuthController::class, 'NewPassword'])->name('newpassword');

        Route::get('/search', [FrontendsController::class, 'globalSearch'])->name('search');

        Route::get('/bookingflow', [FrontendsController::class, 'Bookingflow'])->name('bookingflow');

        Route::match(['get', 'post'], '/select-branch', [FrontendsController::class, 'ChooseExpert'])->name('select-branch');

        Route::match(['get', 'post'], '/choose-expert', [FrontendsController::class, 'ChooseExpert'])->name('choose-expert');


        Route::get('/faq', [FrontendsController::class, 'Faq'])->name('faq');


        Route::get('/package', [PackageController::class, 'index'])->name('package');
        Route::get('/package-checkout', [PackageController::class, 'packagecheckout'])->name('package-checkout');

        Route::get('/membership', [FrontendsController::class, 'Membership'])->name('membership');

        Route::get('/membership-checkout', [FrontendsController::class, 'MembershipCheckout'])->name('membership-checkout');



        Route::get('/product', [UserController::class, 'Product'])->name('product');


        // Route::get('/address', [FrontendsController::class, 'Address'])->name('address');
        // Route::post('/address/store', [UserController::class, 'storeAddress'])->name('frontend.address.store');
        // Route::put('/address/update/{id}', [UserController::class, 'updateAddress'])->name('frontend.address.update');
        // Route::get('/address/delete/{id}', [UserController::class, 'deleteAddress'])->name('frontend.address.delete');
        // Route::get('/address/set-primary/{id}', [UserController::class, 'setPrimaryAddress'])->name('frontend.address.set-primary');
        // Route::get('/address/get-countries', [UserController::class, 'getCountries'])->name('frontend.address.get-countries');
        // Route::get('/address/get-states', [UserController::class, 'getStates'])->name('frontend.address.get-states');
        // Route::get('/address/get-cities', [UserController::class, 'getCities'])->name('frontend.address.get-cities');



        // Route::get('/address/get-countries', [UserController::class, 'getCountries'])->name('frontend.address.get-countries');
        // Route::get('/address/get-states', [UserController::class, 'getStates'])->name('frontend.address.get-states');
        // Route::get('/address/get-cities', [UserController::class, 'getCities'])->name('frontend.address.get-cities');



        Route::get('/expert', [ExpertController::class, 'index'])->name('expert');
        Route::get('/expert/data', [ExpertController::class, 'expertsData'])->name('frontend.experts.data');
        Route::get('/expert-detail/{id}', [ExpertController::class, 'expertDetail'])->name('expert-detail');
        Route::get('/expert-reviews/{id}', [ExpertController::class, 'expertReviews'])->name('expert.reviews');
        Route::get('/expert-reviews/{id}/data', [ExpertController::class, 'getExpertReviewsData'])->name('expert.reviews.data');
        Route::get('/checkout-detail', [ProductController::class, 'checkoutDetail'])->name('checkout-detail');

        Route::get('/packages/data', [PackageController::class, 'packagesData'])->name('frontend.packages.data');

        // API route for getting experts by branch
        Route::get('/experts/by-branch', [FrontendsController::class, 'getExpertsByBranch'])->name('experts.by-branch');

        Route::get('/branch/data', [BranchController::class, 'branchesData'])->name('frontend.branches.data');

        // Blog DataTable AJAX endpoint
        Route::get('blog_index_data', [BlogController::class, 'index_data'])->name('blog.index_data');
    });
});

Route::middleware(['auth'])->group(function () {

    Route::get('/user-notifications', [UserController::class, 'userNotifications'])->name('user-notifications');
    Route::get('/user-notifications-index-data', [UserController::class, 'userNotifications_indexData'])->name('user-notifications.index_data');
    Route::get('/notifications/count', [UserController::class, 'getNotificationCount'])->name('notifications.count');
    // Cart routes
    Route::get('/cart', [ProductController::class, 'Cart'])->name('cart');
    Route::post('/cart/add', [ProductController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/remove', [ProductController::class, 'removeFromCart'])->name('cart.remove');
    Route::get('/cart/count', [ProductController::class, 'getCartCount'])->name('cart.count');
    Route::post('/cart/update', [ProductController::class, 'updateCart'])->name('cart.update');
    Route::get('/cart/data', [ProductController::class, 'cartData'])->name('cart.data');
    Route::get('/cart/summary', [ProductController::class, 'cartSummary'])->name('cart.summary');
    Route::get('/check-out', [ProductController::class, 'CheckOut'])->name('check-out');
    Route::get('/checkout/data', [ProductController::class, 'checkoutData'])->name('checkout.data');
    Route::post('/order/store', [ProductController::class, 'storeOrder'])->name('order.store');
    Route::get('/order/success', [ProductController::class, 'orderSuccess'])->name('order.success');

    Route::get('/bookings', [FrontendsController::class, 'Bookings'])->name('bookings');
    Route::get('/bookings/data', [FrontendsController::class, 'bookingsData'])->name('bookings.data');
    Route::get('/booking-detail', [FrontendsController::class, 'BookingDetail'])->name('booking-detail');

    Route::get('/wallet', [UserController::class, 'Wallet'])->name('wallet');
    Route::get('/refferal', [UserController::class, 'Refferal'])->name('refferal');
    Route::get('/profilemembership', [UserController::class, 'Profilemembership'])->name('profilemembership');
    Route::get('/profilepackage', [UserController::class, 'Profilepackage'])->name('profilepackagep');
    Route::get('/changepassword', [UserController::class, 'Changepassword'])->name('changepassword');

    Route::get('/cart', [ProductController::class, 'Cart'])->name('cart');
    Route::get('/check-out', [ProductController::class, 'CheckOut'])->name('check-out');
    Route::get('/booking-payment', [ProductController::class, 'bookingPayment'])->name('booking-payment');
    Route::get('/order-detail/{order_id}', [ProductController::class, 'OrderDetail'])->name('order-detail');

    Route::get('/wishlist', [UserController::class, 'Wishlist'])->name('wishlist');
    Route::get('/myorder', [UserController::class, 'Myorder'])->name('myorder');
    Route::get('/myorder/data', [UserController::class, 'MyorderData'])->name('myorder.data');
    Route::get('/address', [UserController::class, 'Address'])->name('address');
    Route::get('/address/data', [UserController::class, 'addressData'])->name('frontend.address.data');
    Route::get('/address/{id}', [UserController::class, 'getAddress'])->name('frontend.address.get');
    Route::post('/address/store', [UserController::class, 'storeAddress'])->name('frontend.address.store');
    Route::put('/address/update/{id}', [UserController::class, 'updateAddress'])->name('frontend.address.update');
    Route::delete('/address/delete/{id}', [UserController::class, 'deleteAddress'])->name('frontend.address.delete');
    Route::get('/address/set-primary/{id}', [UserController::class, 'setPrimaryAddress'])->name('frontend.address.set-primary');
    Route::get('/bank-list', [UserController::class, 'BankList'])->name('bank-list');
    Route::get('/wishlist', [UserController::class, 'Wishlist'])->name('wishlist');
    // Route::post('/wishlist/toggle', [\Modules\Product\Http\Controllers\WishListController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('wishlist/data', [WishlistController::class, 'wishlistData'])->name('wishlist.data');
    Route::get('/myorder', [UserController::class, 'Myorder'])->name('myorder');


    // Address routes
    Route::get('/address/get-countries', [ProductController::class, 'getCountries'])->name('frontend.address.get-countries');
    Route::get('/address/get-states', [ProductController::class, 'getStates'])->name('frontend.address.get-states');
    Route::get('/address/get-cities', [ProductController::class, 'getCities'])->name('frontend.address.get-cities');
    Route::post('/address/store', [ProductController::class, 'storeAddress'])->name('frontend.address.store');
    Route::get('/address/{id}', [ProductController::class, 'getAddress'])->name('frontend.address.get');

    // Delivery zones route
    Route::get('/delivery-zones', [ProductController::class, 'getDeliveryZones'])->name('frontend.delivery-zones');
    Route::post('/orders/cancel', [OrdersController::class, 'cancelOrder'])->name('orders.cancel');


    Route::post('/wallet/topup', [WalletController::class, 'topUp'])->name('wallet.topup');
    Route::get('/wallet/test-stripe', [WalletController::class, 'testStripeConfig'])->name('wallet.test.stripe');
    Route::get('/wallet/test-razorpay', [WalletController::class, 'testRazorpayConfig'])->name('wallet.test.razorpay');
    Route::get('/wallet/test-razorpay-currencies', [WalletController::class, 'testRazorpayCurrencies'])->name('wallet.test.razorpay.currencies');
    Route::get('/wallet/check-razorpay-account', [WalletController::class, 'checkRazorpayAccount'])->name('wallet.check.razorpay.account');
    Route::get('/wallet/enable-usd-instructions', [WalletController::class, 'enableUsdInstructions'])->name('wallet.enable.usd.instructions');

    Route::get('/wallet/payment/success', [WalletController::class, 'paymentSuccess'])->name('wallet.payment.success');
    Route::get('/wallet/payment/cancel', [WalletController::class, 'paymentCancel'])->name('wallet.payment.cancel');
    Route::get('/wallet/pay/{method}/{transaction_id}', [WalletController::class, 'gatewayRedirect'])->name('wallet.gateway.redirect');
    Route::get('/profilepackage/data', [Modules\Frontend\Http\Controllers\Backend\UserController::class, 'packageData'])->name('profilepackage.data');
    Route::post('/wallet/payment/verify/razorpay', [WalletController::class, 'verifyRazorpayPayment'])
        ->name('wallet.payment.verify');


    // Add this route for the bank list page
    Route::get('/bank-list', [BankController::class, 'index'])->name('bank-list');

    // Wallet Routes
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('/wallet/history/data', [WalletController::class, 'historyData'])->name('wallet.history.data');
    Route::get('/wallet/history', [WalletController::class, 'index'])->name('wallet.history');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');

    Route::get('/banks/data', [BankController::class, 'getBankCardsData'])->name('bank.data');

    Route::get('/invoice/download/{order_id}', [Modules\Frontend\Http\Controllers\Backend\ProductController::class, 'downloadInvoice'])->name('invoice.download');

    Route::get('/get-coupon-details', [FrontendsController::class, 'getDetails'])->name('get-coupon-details');

    Route::post('/wishlist/add', [WishlistController::class, 'add'])->name('wishlist.add');
    Route::post('/wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::post('set-branch', [\Modules\Frontend\Http\Controllers\Backend\FrontendsController::class, 'setBranch'])->name('frontend.setBranch');

    // Payment routes
    Route::get('payment/checkout', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::post('payment/process', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'ProductPaymentProccess'])->name('payment.process');
    Route::get('payment/success/{gateway}', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');
    Route::get('payment/cancel/{gateway}', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'cancel'])->name('payment.cancel');

    Route::post('/product/payment/razorpay', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'productRazorpayCheckout'])->name('product.payment.razorpay');
    Route::post('/product/payment/stripe', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'productStripeCheckout'])->name('product.payment.stripe');
    Route::get('/product/payment/stripe-success', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'productStripeSuccess'])->name('product.payment.stripe.success');
    Route::post('/product/razorpay/success', [\Modules\Frontend\Http\Controllers\PaymentController::class, 'productRazorpaySuccess'])->name('product.razorpay.success');

    // Temporarily removing auth middleware for testing
    Route::post('/bookings/cancel/{id}', [FrontendsController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/bookings/{id}/details', [FrontendsController::class, 'details'])->name('bookings.details');
    Route::post('/bookings/reschedule/{id}', [FrontendsController::class, 'reschedule'])->name('bookings.reschedule');
    Route::get('/bookings/data', [FrontendsController::class, 'bookingsData'])->name('bookings.data');
    Route::post('/review/submit', [ReviewController::class, 'submit'])->name('review.submit');
    Route::post('/review/delete', [ReviewController::class, 'delete'])->name('review.delete');

    Route::get('payment/process', function () {
        return response()->view('payment.method_not_allowed', [], 405);
    });

    Route::post('/booking/process-payment', [BookingPaymentController::class, 'processPayment'])->name('booking.process-payment');
    Route::get('booking/stripe-success', [BookingPaymentController::class, 'stripeSuccess'])->name('booking.stripe.success');
    Route::get('booking/paystack-success', [BookingPaymentController::class, 'paystackSuccess'])->name('booking.paystack.success');
    Route::get('booking/flutterwave-success', [BookingPaymentController::class, 'flutterwaveSuccess'])->name('booking.flutterwave.success');
    Route::get('/booking/razorpay/success', [BookingPaymentController::class, 'razorpaySuccess'])->name('booking.razorpay.success');
    Route::post('/booking/paypal-success', [BookingPaymentController::class, 'paypalSuccess'])->name('booking.paypal-success');
    Route::post('/booking/midtrans-success', [BookingPaymentController::class, 'paypalSuccess'])->name('booking.midtrans.success');

    Route::prefix('bank')->group(function () {
        Route::get('/', [BankController::class, 'index'])->name('bank.index');
        Route::get('/cards/data', [BankController::class, 'getBankCardsData'])->name('bank.cards.data');
        Route::get('/data', [BankController::class, 'getBankCardsData'])->name('frontend.banks.data');
        Route::post('/', [BankController::class, 'store'])->name('bank.store');
        Route::get('/{bank}/edit', [BankController::class, 'edit'])->name('bank.edit');
        Route::put('/{bank}', [BankController::class, 'update'])->name('bank.update');
        Route::delete('/{bank}', [BankController::class, 'destroy'])->name('bank.destroy');
        Route::post('/{bank}/set-default', [BankController::class, 'setDefault'])->name('bank.setDefault');
        Route::get('/cards-data', [BankController::class, 'getBankCardsData'])->name('bank.cards-data');
    });


    Route::get('/bookings/{id}/detail-page', [\Modules\Frontend\Http\Controllers\Backend\FrontendsController::class, 'bookingDetailPage'])->name('bookings.detail-page');
    Route::get('/bookings/{id}/download-invoice', [\Modules\Frontend\Http\Controllers\Backend\FrontendsController::class, 'downloadBookingInvoice'])->name('booking.invoice.download');
});



// Test route to check if routing is working
Route::get('/test-booking/{id}', function ($id) {
    return response()->json(['success' => true, 'message' => 'Test route working', 'id' => $id]);
})->name('test.booking');

Route::get('/terms', [FrontendsController::class, 'terms'])->name('terms');
Route::get('/privacy', [FrontendsController::class, 'privacy'])->name('privacy');
Route::get('/support', [FrontendsController::class, 'support'])->name('support');

Route::get('/refund', [FrontendsController::class, 'refund'])->name('refund');
Route::get('/data-deletion', [FrontendsController::class, 'dataDeletion'])->name('data.deletion');
Route::get('/profilepackage/data', [Modules\Frontend\Http\Controllers\Backend\UserController::class, 'packageData'])->name('profilepackage.data');
Route::post('/inquiry/store', [Modules\Frontend\Http\Controllers\Backend\UserController::class, 'storeInquiry'])->name('inquiry.store');
