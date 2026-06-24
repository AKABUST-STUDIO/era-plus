<?php

namespace App\Filament\User\Pages;

use App\Enums\SupportRequestStatus;
use App\Mail\SupportRequestReceived;
use App\Models\SupportRequest;
use App\Models\SupportRequestMessage;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class Support extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.user.pages.support';

    /**
     * @var array<string, mixed>
     */
    public ?array $createData = [];

    public function mount(): void
    {
        $this->createForm->fill([
            'priority' => 'normal',
        ]);
    }

    public function getTitle(): string
    {
        return __('user.support.title');
    }

    public function createForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('createData')
            ->components([
                Section::make(__('user.support.new.heading'))
                    ->schema([
                        TextInput::make('subject')->label(__('user.support.subject'))->required()->maxLength(255),
                        Select::make('priority')
                            ->label(__('user.support.priority'))
                            ->options([
                                'low' => __('user.support.priorities.low'),
                                'normal' => __('user.support.priorities.normal'),
                                'high' => __('user.support.priorities.high'),
                                'urgent' => __('user.support.priorities.urgent'),
                            ])
                            ->required(),
                        Textarea::make('body')->label(__('user.support.body'))->required()->rows(5)->columnSpanFull(),
                    ])
                    ->footerActions([
                        Action::make('submit')
                            ->label(__('user.support.submit'))
                            ->action(fn () => $this->submit()),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $data = $this->createForm->getState();

        $request = SupportRequest::create([
            'user_id' => auth()->id(),
            'subject' => $data['subject'],
            'body' => $data['body'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => SupportRequestStatus::Open->value,
        ]);

        // Seed the message thread with the original body so future replies
        // share a single chronological view.
        $request->messages()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
            'is_staff_reply' => false,
        ]);

        Mail::to(auth()->user()->email)->queue(new SupportRequestReceived($request));

        $this->createForm->fill(['priority' => 'normal']);

        Notification::make()->title(__('notifications.support_submitted'))->success()->send();
    }

    public function reply(SupportRequest $request, string $body): void
    {
        abort_unless($request->user_id === auth()->id(), 403);

        $request->messages()->create([
            'user_id' => auth()->id(),
            'body' => $body,
            'is_staff_reply' => false,
        ]);

        if ($request->status === SupportRequestStatus::Resolved) {
            $request->reopen();
        }

        Notification::make()->title(__('user.support.actions.reply_sent'))->success()->send();
    }

    public function markResolved(SupportRequest $request): void
    {
        abort_unless($request->user_id === auth()->id(), 403);

        $request->markResolved();

        Notification::make()->title(__('user.support.actions.resolved_sent'))->success()->send();
    }

    public function markReopen(SupportRequest $request): void
    {
        abort_unless($request->user_id === auth()->id(), 403);

        $request->reopen();

        Notification::make()->title(__('user.support.actions.reopened_sent'))->success()->send();
    }

    public function markStaffRepliesRead(SupportRequest $request): void
    {
        abort_unless($request->user_id === auth()->id(), 403);

        SupportRequestMessage::query()
            ->where('support_request_id', $request->id)
            ->where('is_staff_reply', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadStaffRepliesCount(): int
    {
        return SupportRequestMessage::query()
            ->whereHas('supportRequest', fn (Builder $q) => $q->where('user_id', auth()->id()))
            ->where('is_staff_reply', true)
            ->whereNull('read_at')
            ->count();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SupportRequestMessage::query()
            ->whereHas('supportRequest', fn (Builder $q) => $q->where('user_id', auth()->id()))
            ->where('is_staff_reply', true)
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SupportRequest::query()->where('user_id', auth()->id()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')->label(__('user.support.subject'))->searchable()->sortable(),
                TextColumn::make('priority')->label(__('user.support.priority'))->badge()->sortable(),
                TextColumn::make('status')->label(__('user.support.status'))->badge()->sortable(),
                IconColumn::make('unread')
                    ->label(__('user.support.unread'))
                    ->state(fn (SupportRequest $r): bool => $r->hasUnreadStaffReply())
                    ->trueIcon('lucide-mail-warning')
                    ->falseIcon(null)
                    ->color('warning'),
                TextColumn::make('created_at')->label(__('user.support.opened'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SupportRequestStatus::class),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label(__('user.support.actions.reply'))
                    ->icon('lucide-message-square')
                    ->modalHeading(fn (SupportRequest $r): string => __('user.support.actions.reply_to', ['subject' => $r->subject]))
                    ->form([
                        Textarea::make('body')->label(__('user.support.body'))->required()->rows(5),
                    ])
                    ->action(fn (SupportRequest $r, array $data) => $this->reply($r, $data['body'])),
                Action::make('resolve')
                    ->label(__('user.support.actions.resolve'))
                    ->icon('lucide-check')
                    ->color('success')
                    ->visible(fn (SupportRequest $r): bool => $r->status !== SupportRequestStatus::Resolved
                        && $r->status !== SupportRequestStatus::Closed)
                    ->action(fn (SupportRequest $r) => $this->markResolved($r)),
                Action::make('reopen')
                    ->label(__('user.support.actions.reopen'))
                    ->icon('lucide-rotate-ccw')
                    ->color('warning')
                    ->visible(fn (SupportRequest $r): bool => $r->status === SupportRequestStatus::Resolved)
                    ->action(fn (SupportRequest $r) => $this->markReopen($r)),
            ]);
    }
}
