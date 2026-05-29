<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Circular Interna {{ $ticket->circular_cite }}</title>
    <style>
        @page { size: letter; margin: 1.5cm; }
        body {
            background: #fff;
            color: #111;
            font-family: "Times New Roman", serif;
            font-size: 16px;
            line-height: 1.18;
        }
        .sheet {
            max-width: 760px;
            min-height: 940px;
            margin: 0 auto;
            padding: 34px 46px 42px;
            background: #fff;
        }
        .top {
            position: relative;
            text-align: center;
            min-height: 116px;
        }
        .brand {
            font-size: 20px;
            font-style: italic;
            line-height: 1.15;
        }
        .seal {
            position: absolute;
            right: 12px;
            top: 6px;
            width: 86px;
            height: 86px;
            border: 2px solid #333;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
        }
        .internal { margin-top: 10px; font-family: Arial, sans-serif; font-size: 13px; letter-spacing: .3px; }
        .title { margin-top: 14px; font-family: Arial, sans-serif; font-size: 20px; }
        .cite { margin: 16px 24px 28px 0; text-align: right; font-family: Arial, sans-serif; font-weight: bold; }
        .line { display: grid; grid-template-columns: 70px 1fr; gap: 12px; margin-top: 10px; }
        .label { font-weight: bold; }
        .ref { margin-top: 28px; }
        .instructions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26px;
            max-width: 520px;
            margin: 30px auto 24px;
        }
        .instruction { display: grid; grid-template-columns: 22px 22px 1fr; align-items: center; min-height: 21px; }
        .box {
            width: 19px;
            height: 19px;
            border: 1.6px solid #333;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            font-size: 16px;
            line-height: 1;
        }
        .object-title { margin-left: 48px; font-weight: bold; }
        .object-box {
            min-height: 150px;
            border: 2px solid #333;
            margin: 4px 28px 0;
            padding: 10px;
            white-space: pre-wrap;
        }
        .dates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin: 0 28px;
            border: 2px solid #333;
            border-top: 0;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        .dates div { padding: 5px 10px; text-align: center; }
        .dates div:first-child { border-right: 2px solid #333; }
        .note {
            margin: 30px 44px 0;
            text-align: center;
            font-style: italic;
        }
        .actions { position: fixed; right: 20px; top: 20px; }
        .actions button { background: #1f2937; color: white; border: 0; border-radius: 6px; padding: 8px 12px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .sheet { padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $selected = array_map('intval', $ticket->physical_instructions ?? []);
        $instructions = \App\Models\Ticket::PHYSICAL_INSTRUCTIONS;
        $left = array_slice($instructions, 0, 7, true);
        $right = array_slice($instructions, 7, 7, true);
    @endphp

    <div class="actions">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <main class="sheet">
        <div class="top">
            <div class="brand">
                Gobierno Autonomo Departamental de Tarija<br>
                Secretaria Dptal de Finanzas Publicas y Planificacion<br>
                Direccion de Tecnologias de Informacion<br>
                Tarija - Bolivia
            </div>
            <div class="seal">GADT<br>DTI</div>
            <div class="internal">PARA CIRCULACION INTERNA</div>
            <div class="title">Circular Interna</div>
        </div>

        <div class="cite">{{ $ticket->circular_cite }}</div>

        <div class="line">
            <div class="label">A&nbsp;&nbsp;::</div>
            <div>{{ $ticket->assignee?->name ?? 'Sin asignar' }}</div>
        </div>
        <div class="line">
            <div class="label">Atte.::</div>
            <div>{{ $ticket->user?->name }}{{ $ticket->user?->office ? ' ('.$ticket->user->office->name.')' : '' }}</div>
        </div>

        <div class="line ref">
            <div class="label">Ref. ::</div>
            <div>{{ $ticket->reference ?? $ticket->subject }}</div>
        </div>

        <div class="instructions">
            <div>
                @foreach($left as $number => $label)
                    <div class="instruction">
                        <span>{{ $number }}</span>
                        <span class="box">{{ in_array($number, $selected, true) ? 'X' : '' }}</span>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <div>
                @foreach($right as $number => $label)
                    <div class="instruction">
                        <span>{{ $number }}</span>
                        <span class="box">{{ in_array($number, $selected, true) ? 'X' : '' }}</span>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="object-title">Objeto</div>
        <div class="object-box">{{ $ticket->message }}</div>

        <div class="dates">
            <div>Entregado el&nbsp;&nbsp; {{ $ticket->created_at->format('d') }} / {{ $ticket->created_at->format('m') }} / {{ $ticket->created_at->format('Y') }}</div>
            <div>Devuelto el&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / {{ $ticket->created_at->format('Y') }}</div>
        </div>

        <div class="note">
            Nota: Esta papeleta no debe ser separada ni extraviada del documento al cual se encuentre adherida por constituir parte del mismo
        </div>
    </main>
</body>
</html>
