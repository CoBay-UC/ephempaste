CREATE TABLE IF NOT EXISTS pastes (
    code VARCHAR(32) PRIMARY KEY,
    content TEXT NOT NULL,
    password_salt CHAR(32) NULL,
    password_hash CHAR(64) NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    expires_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT pastes_code_length
        CHECK (char_length(code) BETWEEN 4 AND 32),

    CONSTRAINT pastes_content_length
        CHECK (char_length(content) BETWEEN 1 AND 5000),

    CONSTRAINT pastes_password_pair
        CHECK (
            (password_salt IS NULL AND password_hash IS NULL)
            OR
            (password_salt IS NOT NULL AND password_hash IS NOT NULL)
        )
);

CREATE INDEX IF NOT EXISTS idx_pastes_expires_at ON pastes (expires_at);
