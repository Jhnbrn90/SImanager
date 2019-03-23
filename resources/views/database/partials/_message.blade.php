@if (session()->has('message'))
<div class="container mx-auto p-6 text-center">
    <div class="bg-blue-lightest border border-blue-light text-blue-dark px-4 py-3 rounded relative" role="alert">
      
      <strong class="font-bold">{{ session('message') }}</strong>
    </div>
</div>
@endif

@if (session()->has('error'))
<div class="container mx-auto p-6 text-center">
    <div class="bg-red-lightest border border-red-light text-red-dark px-4 py-3 rounded relative" role="alert">
      
      <strong class="font-bold">{{ session('error') }}</strong>
    </div>
</div>
@endif
