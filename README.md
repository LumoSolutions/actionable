# ⚡ Actionable
![hero](https://github.com/user-attachments/assets/9d7e5e3b-cb82-4fff-a242-847d16051d18)

> **Transform your Laravel code into clean, testable, and reusable actions.** Say goodbye to bloated controllers and hello to elegantly organized business logic!

<div align="center">

[![CI Pipeline](https://github.com/LumoSolutions/actionable/actions/workflows/build.yml/badge.svg)](https://github.com/LumoSolutions/actionable/actions/workflows/build.yml)
[![codecov](https://codecov.io/gh/LumoSolutions/actionable/branch/main/graph/badge.svg)](https://codecov.io/gh/LumoSolutions/actionable)
[![Latest Stable Version](https://poser.pugx.org/lumosolutions/actionable/v/stable)](https://packagist.org/packages/lumosolutions/actionable)
[![Total Downloads](https://poser.pugx.org/lumosolutions/actionable/downloads)](https://packagist.org/packages/lumosolutions/actionable)
[![License](https://img.shields.io/github/license/LumoSolutions/actionable)](LICENSE)

**[Installation](#-installation)** • **[Quick Start](#-quick-start)** • **[Features](#-key-features)** • **[Documentation](#-documentation)** • **[Examples](#-real-world-examples)**

</div>

## 💡 Why Actionable?

Ever found yourself writing the same business logic patterns over and over? Controllers getting too fat? Service classes becoming a mess? **Actionable is here to save the day!**

```php
// ❌ The old way - Fat controllers, messy code
class UserController extends Controller
{
    public function register(Request $request)
    {
        // Validation logic...
        // User creation logic...
        // Email sending logic...
        // Queue processing...
        // 200 lines later...
    }
}

// ✅ The Actionable way - Clean, focused, reusable
RegisterUser::run($userData);
```

## 🎯 Key Features

### 🏃‍♂️ **Runnable Actions**
Execute business logic with a single, expressive call. No more hunting through service classes!

### 📬 **Dispatchable Actions**
Seamlessly queue your actions for background processing. It's as easy as changing `run()` to `dispatch()`!

### 🔄 **Smart Array Conversion**
Convert between arrays and objects effortlessly with our powerful attribute system. Perfect for APIs!

### 🛠️ **Artisan Generators**
Scaffold Actions and DTOs in seconds with our intuitive Artisan commands.

### 🎨 **Flexible Attributes**
Fine-tune serialization behavior with elegant attributes like `#[FieldName]`, `#[DateFormat]`, and more!

## 📦 Installation

```bash
composer require lumosolutions/actionable
```

That's it! No configuration needed. Start writing better code immediately.

## 🚀 Quick Start

### Your First Action in 30 Seconds

**1️⃣ Generate an action:**
```bash
php artisan make:action SendWelcomeEmail
```

**2️⃣ Define your logic:**
```php
class SendWelcomeEmail
{
    use IsRunnable;

    public function handle(string $email, string $name): void
    {
        Mail::to($email)->send(new WelcomeEmail($name));
    }
}
```

**3️⃣ Use it anywhere:**
```php
SendWelcomeEmail::run('user@example.com', 'John Doe');
```

**That's it!** Clean, testable, reusable. 🎉

## 📚 Documentation

### ⚡ Actions

Actions are the heart of your application's business logic. They're single-purpose classes that do one thing and do it well.

#### Basic Actions

```php
class CalculateOrderTotal
{
    use IsRunnable;

    public function handle(Order $order): float
    {
        return $order->items->sum(fn($item) => $item->price * $item->quantity);
    }
}

// Usage
$total = CalculateOrderTotal::run($order);
```

#### Queueable Actions

Need background processing? Just add a trait!

```php
class ProcessVideoUpload
{
    use IsRunnable, IsDispatchable;

    public function handle(Video $video): void
    {
        // Heavy processing logic here
    }
}

// Run synchronously
ProcessVideoUpload::run($video);

// Or dispatch to queue
ProcessVideoUpload::dispatch($video);

// Use a specific queue
ProcessVideoUpload::dispatchOn('video-processing', $video);
```

### 🗄️ Data Transfer Objects (DTOs)

DTOs with superpowers! Convert between arrays and objects seamlessly.

```php
class ProductData
{
    use ArrayConvertible;

    public function __construct(
        public string $name,
        public float $price,
        public int $stock
    ) {}
}

// From request data
$product = ProductData::fromArray($request->validated());

// To API response
return response()->json($product->toArray());
```

### 🏷️ Powerful Attributes

#### `#[FieldName]` - API-Friendly Naming

```php
class UserResponse
{
    use ArrayConvertible;

    public function __construct(
        #[FieldName('user_id')]
        public int $userId,
        
        #[FieldName('full_name')]
        public string $fullName
    ) {}
}
```

#### `#[DateFormat]` - Date Formatting Made Easy

```php
class EventData
{
    use ArrayConvertible;

    public function __construct(
        #[DateFormat('Y-m-d')]
        public DateTime $date,
        
        #[DateFormat('H:i')]
        public DateTime $startTime
    ) {}
}
```

#### `#[ArrayOf]` - Handle Nested Objects

```php
class ShoppingCart
{
    use ArrayConvertible;

    public function __construct(
        #[ArrayOf(CartItem::class)]
        public array $items
    ) {}
}
```

#### `#[Ignore]` - Keep Secrets Secret

```php
class UserAccount
{
    use ArrayConvertible;

    public function __construct(
        public string $email,
        
        #[Ignore]
        public string $password,
        
        #[Ignore]
        public string $apiSecret
    ) {}
}
```

### 🛠️ Artisan Commands

Generate boilerplate with style:

```bash
# Basic action
php artisan make:action ProcessOrder

# Queueable action
php artisan make:action SendNewsletter --dispatchable

# Invokable action
php artisan make:action CalculateShipping --invokable

# DTO with array conversion
php artisan make:dto OrderData
```

## 🌟 Real-World Examples

### E-commerce Order Processing

```php
// The DTO
class OrderData
{
    use ArrayConvertible;

    public function __construct(
        #[FieldName('customer_email')]
        public string $customerEmail,
        
        #[ArrayOf(OrderItemData::class)]
        public array $items,
        
        #[FieldName('discount_code')]
        public ?string $discountCode = null
    ) {}
}

// The Action
class ProcessOrder
{
    use IsRunnable, IsDispatchable;

    public function handle(OrderData $orderData): Order
    {
        $order = DB::transaction(function () use ($orderData) {
            $order = Order::create([...]);
            
            // Process items
            foreach ($orderData->items as $item) {
                $order->items()->create([...]);
            }
            
            // Apply discount
            if ($orderData->discountCode) {
                ApplyDiscount::run($order, $orderData->discountCode);
            }
            
            return $order;
        });

        // Queue follow-up actions
        SendOrderConfirmation::dispatch($order);
        UpdateInventory::dispatch($order);
        
        return $order;
    }
}

// Usage - It's this simple!
$orderData = OrderData::fromArray($request->validated());
$order = ProcessOrder::run($orderData);
```

### User Registration Flow

```php
class RegisterUser
{
    use IsRunnable;

    public function handle(RegistrationData $data): User
    {
        $user = CreateUser::run($data);
        
        SendWelcomeEmail::dispatch($user);
        NotifyAdmins::dispatch($user);
        TrackRegistration::dispatch($user, $data->referralSource);
        
        return $user;
    }
}
```

## 🤲 Contributing

We love contributions! Whether it's a bug fix, new feature, or improvement to our docs - we appreciate it all.  Please feel free to submit a pull request or open an issue.

## 📄 License

Actionable is open-sourced software licensed under the [MIT license](LICENSE).

## 💬 Support & Community

- 🐛 **Found a bug?** [Open an issue](https://github.com/LumoSolutions/actionable/issues)
- 💡 **Have an idea?** [Start a discussion](https://github.com/LumoSolutions/actionable/discussions)
- 🔒 **Security concern?** Email me at richard@lumosolutions.org

---

<div align="center">

**Built with ❤️ by [Lumo Solutions](https://lumosolutions.org)**

*Actionable: Making Laravel development more enjoyable, one action at a time.*

</div>
