export enum BookingStatus {
    PENDING = 'pending',
    CONFIRMED = 'confirmed',
    CANCELLED = 'cancelled',
    MODIFIED = 'modified',
    COMPLETED = 'completed',
}

export enum PaymentStatus {
    UNPAID = 'unpaid',
    PARTIALLY_PAID = 'partiallyPaid',
    PAID = 'paid',
    REFUNDED = 'refunded',
}

export enum ConversationStatus {
    ACTIVE = 'active',
    PENDING = 'pending',
    CLOSED = 'closed',
    RESOLVED = 'resolved',
}

export enum ConversationChannel {
    CHAT = 'chat',
    EMAIL = 'email',
    WHATSAPP = 'whatsapp',
}

export enum SupplierStatus {
    PENDING = 'pending',
    ACTIVE = 'active',
    INACTIVE = 'inactive',
}

export enum CampaignStatus {
    DRAFT = 'draft',
    SCHEDULED = 'scheduled',
    SENDING = 'sending',
    SENT = 'sent',
    CANCELLED = 'cancelled',
}
