<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Квиток на квест</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; background-color: #f4f4f5; padding: 20px; color: #333; }
        .ticket-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 2px solid #8B5CF6; }
        .header { background-color: #8B5CF6; color: #ffffff; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0 0 0; font-size: 16px; opacity: 0.9; }
        .content { padding: 30px; }
        .greeting { font-size: 18px; margin-bottom: 20px; }
        .details-box { background-color: #faf5ff; border: 1px dashed #8B5CF6; border-radius: 8px; padding: 20px; }
        .info-row { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .info-row:last-child { border-bottom: none; }
        .label { font-size: 14px; color: #6b7280; display: inline-block; width: 150px; }
        .value { font-size: 16px; font-weight: bold; color: #111827; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; border-top: 1px dashed #d1d5db; }
    </style>
</head>
<body>
<div class="ticket-container">
    <div class="header">
        <h1>ONEAQUESTS</h1>
        <p>Електронний квиток #{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</p>
    </div>
    <div class="content">
        <div class="greeting">
            Вітаємо, <strong>{{ $booking->guest_name ?? $booking->user?->name ?? 'Гість' }}</strong>!<br>
            Ваша оплата успішна. Чекаємо на вашу команду!
        </div>
        <div class="details-box">
            <div class="info-row">
                <span class="label">Квест-кімната:</span>
                <span class="value">{{ $booking->room->name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Дата та час:</span>
                <span class="value">{{ Carbon::parse($booking->start_time)->format('d.m.Y о H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Команда:</span>
                <span class="value">{{ $booking->players_count }} гравців</span>
            </div>
            <div class="info-row">
                <span class="label">Сплачено:</span>
                <span class="value">{{ $booking->total_price / 100 }} грн</span>
            </div>
        </div>
        @if(!isset($isPdf))
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ URL::signedRoute('ticket.download', ['booking' => $booking->id]) }}"
                   style="background-color: #8B5CF6; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block;">
                    Завантажити PDF-квиток
                </a>
            </div>
        @endif
    </div>
    <div class="footer">
        Будь ласка, приходьте за 10 хвилин до початку гри.<br>
        Покажіть цей квиток (або PDF-додаток) адміністратору.
    </div>
</div>
</body>
</html>
