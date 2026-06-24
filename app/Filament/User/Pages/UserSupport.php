<?php

namespace App\Filament\User\Pages;

use App\Enums\SupportRequestStatus;
use App\Mail\SupportRequestReceived;
use App\Models\SupportRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class UserSupport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.user.pages.user-support';

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
        return 'Support';
    }

    public function createForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('createData')
            ->components([
                Section::make('New support request')
                    ->schema([
                        TextInput::make('subject')->required()->maxLength(255),
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->required(),
                        Textarea::make('body')->required()->rows(5)->columnSpanFull(),
                    ])
                    ->footerActions([
                        Action::make('submit')
                            ->label('Submit')
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

        Mail::to(auth()->user()->email)->queue(new SupportRequestReceived($request));

        $this->createForm->fill(['priority' => 'normal']);

        Notification::make()->title('Support request submitted')->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SupportRequest::query()->where('user_id', auth()->id()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')->searchable()->sortable(),
                TextColumn::make('priority')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SupportRequestStatus::class),
            ]);
    }
}
