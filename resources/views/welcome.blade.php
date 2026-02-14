<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Misdinar Servatius</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-blue-800 p-4 text-white shadow-lg">
        <div class="container mx-auto font-bold">LMS Misdinar St. Servatius</div>
    </nav>

    <main class="container mx-auto mt-10 p-4">
        <form action="/submit-ujian" method="POST">
            @csrf
            <button type="submit" class="w-full bg-green-600 text-white p-3 rounded-lg font-bold hover:bg-green-700">
                Kirim Jawaban
            </button>
        </form>
    </main>
</body>
</html>