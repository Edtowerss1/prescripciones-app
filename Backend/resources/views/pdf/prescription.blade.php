<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Prescripción Médica</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            margin: 30px;
        }
        h1 {
            text-align: center;
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 24px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2563eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            padding: 6px 10px;
            text-align: left;
            border: 1px solid #d1d5db;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
            font-size: 11px;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-table td:first-child {
            width: 160px;
            font-weight: 600;
            background-color: #f9fafb;
        }
        .info-table td:last-child {
            color: #4b5563;
        }
        .items-table th {
            background-color: #2563eb;
            color: #ffffff;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <h1>Prescripción Médica</h1>

    <table class="info-table">
        <tr>
            <td>Código</td>
            <td>{{ $prescription->code }}</td>
        </tr>
        <tr>
            <td>Fecha de creación</td>
            <td>{{ $prescription->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Estado</td>
            <td>{{ ucfirst($prescription->status) }}</td>
        </tr>
        <tr>
            <td>Paciente</td>
            <td>{{ $prescription->patient->user->name }}</td>
        </tr>
        <tr>
            <td>Médico</td>
            <td>{{ $prescription->doctor->user->name }}</td>
        </tr>
        @if ($prescription->notes)
        <tr>
            <td>Notas</td>
            <td>{{ $prescription->notes }}</td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Medicamento</th>
                <th>Dosis</th>
                <th>Cantidad</th>
                <th>Indicaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prescription->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->dosage ?? '—' }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td>{{ $item->instructions ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #9ca3af;">Sin medicamentos</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento generado electrónicamente · {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
